<?php

namespace Webkul\MCP\Tools\Settings;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;

class SettingSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search for settings (Channels or Locales) in UnoPim using generic filters and pagination.';

    /**
     * The tool's name.
     */
    public string $name = 'search_settings';

    public function __construct(
        protected ChannelRepository $channelRepository,
        protected LocaleRepository $localeRepository,
        protected UnoPimQueryBuilder $queryBuilder
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:channels,locales'],
            'filters' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ]);

        $type = $validated['type'];
        $repository = $type === 'channels' ? $this->channelRepository : $this->localeRepository;

        $query = $repository->getModel()->query();

        if (! empty($validated['filters'])) {
            $this->queryBuilder->applyFilters($query, $validated['filters']);
        }

        $paginator = $this->queryBuilder->paginate(
            $query->orderByDesc('id'),
            (int) ($validated['limit'] ?? 25),
            $validated['cursor'] ?? null
        );

        return Response::json([
            'type' => $type,
            'count' => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'results' => $paginator->map(fn ($item) => $item->toArray())->values()->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('The type of settings to search for (channels or locales).')
                ->enum(['channels', 'locales'])
                ->required(),
            'filters' => $schema->array()
                ->description('List of filters: [{field, operator, value}].')
                ->items(
                    $schema->object([
                        'field' => $schema->string()->description('The field to filter by (e.g., code, status).'),
                        'operator' => $schema->string()->description('The comparison operator.'),
                        'value' => $schema->string()->description('The value to compare against.'),
                    ])
                ),
            'limit' => $schema->integer()
                ->description('Number of results per page.')
                ->default(25),
            'cursor' => $schema->string()
                ->description('Pagination cursor.'),
        ];
    }
}
