<?php

namespace Webkul\MCP\Tools\Settings;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\MCP\Tools\BaseMcpTool;

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

            return Response::error($e->getMessage());
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
