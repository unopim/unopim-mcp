<?php

namespace Webkul\MCP\Tools\Settings;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\MCP\Tools\BaseMcpTool;

class SettingUpsertTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more settings (Channels or Locales) in UnoPim. Automatically determines create vs update based on code existence.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_settings';

    public function __construct(
        protected ChannelRepository $channelRepository,
        protected LocaleRepository $localeRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:channels,locales'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.code' => ['required', 'string', 'max:100'],
            // Additional fields depend on type, but repositories handle them via $attributes
        ]);

        $type = $validated['type'];
        $repository = $type === 'channels' ? $this->channelRepository : $this->localeRepository;

        DB::beginTransaction();

        try {
            $results = [];

            foreach ($validated['items'] as $itemData) {
                $code = $itemData['code'];

                $item = $repository->findOneByField('code', $code);

                if ($item) {
                    $item = $repository->update($itemData, $item->id);
                    $results[] = ['id' => $item->id, 'code' => $item->code, 'action' => 'updated'];
                } else {
                    $item = $repository->create($itemData);
                    $results[] = ['id' => $item->id, 'code' => $item->code, 'action' => 'created'];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return Response::json([
            'success' => true,
            'type' => $type,
            'results' => $results,
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('The type of settings to upsert (channels or locales).')
                ->enum(['channels', 'locales'])
                ->required(),
            'items' => $schema->array()
                ->description('Array of settings to create or update (max 50).')
                ->items(
                    $schema->object([
                        'code' => $schema->string()->description('The unique code (identifier).')->required(),
                        // Dynamic fields like name, hostname, status, etc.
                    ])
                )->required(),
        ];
    }
}
