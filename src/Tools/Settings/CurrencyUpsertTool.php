<?php

namespace Webkul\MCP\Tools\Settings;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsDestructive]
#[IsIdempotent]
class CurrencyUpsertTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more currencies in UnoPim. Automatically determines create vs update based on code existence.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_currencies';

    public function __construct(
        protected CurrencyRepository $currencyRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.code' => ['required', 'string', 'size:3'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.symbol' => ['nullable', 'string', 'max:10'],
            'items.*.status' => ['nullable', 'boolean'],
        ]);

        $this->ensureNotDisablingCurrencyInUse($validated['items']);

        DB::beginTransaction();
        $results = [];

        try {
            foreach ($validated['items'] as $itemData) {
                $code = $itemData['code'];
                $currency = $this->currencyRepository->findOneByField('code', $code);

                if ($currency) {
                    $currency = $this->currencyRepository->update($itemData, $currency->id);
                    $results[] = ['id' => $currency->id, 'code' => $currency->code, 'action' => 'updated'];
                } else {
                    $currency = $this->currencyRepository->create($itemData);
                    $results[] = ['id' => $currency->id, 'code' => $currency->code, 'action' => 'created'];
                }
            }

            DB::commit();

            return Response::json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Refuse, like the admin currency form, to disable a currency that a channel still uses.
     *
     * @param  array<int, array<string, mixed>>  $items
     *
     * @throws ValidationException
     */
    protected function ensureNotDisablingCurrencyInUse(array $items): void
    {
        $disabled = collect($items)->filter(fn (array $item): bool => array_key_exists('status', $item) && $item['status'] !== null && ! $item['status']);

        if ($disabled->isEmpty()) {
            return;
        }

        $currencies = $this->currencyRepository->findWhereIn('code', $disabled->pluck('code')->all())->keyBy('code');

        foreach ($disabled as $index => $item) {
            $currency = $currencies->get($item['code']);

            if ($currency && $this->currencyRepository->checkCurrencyBeingUsed($currency->id)) {
                throw ValidationException::withMessages([
                    "items.{$index}.status" => trans('mcp::app.tools.upsert-currencies.can-not-disable', ['code' => $currency->code]),
                ]);
            }
        }
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'items' => $schema->array()
                ->description('Array of currencies to create or update (max 50).')
                ->items(
                    $schema->object([
                        'code' => $schema->string()->description('The 3-letter currency code (e.g., USD, EUR).')->required(),
                        'name' => $schema->string()->description('The name of the currency.'),
                        'symbol' => $schema->string()->description('The currency symbol.'),
                        'status' => $schema->boolean()->description('Status of the currency.'),
                    ])
                )->required(),
        ];
    }
}
