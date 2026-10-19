<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class AttributeSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search for product attributes in the UnoPim catalog using generic filters and pagination.';

    /**
     * The tool's title.
     */
    protected string $title = 'Search attributes';

    /**
     * The tool's name.
     */
    public string $name = 'search_attributes';

    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected UnoPimQueryBuilder $queryBuilder
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'filters' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = $this->attributeRepository->getModel()->query();

        if (! empty($validated['filters'])) {
            $this->queryBuilder->applyFilters($query, $validated['filters']);
        }

        $paginator = $this->queryBuilder->paginate(
            $query->orderByDesc('id'),
            (int) ($validated['limit'] ?? 25),
            $validated['cursor'] ?? null
        );

        return Response::json([
            'count' => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'attributes' => $paginator->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'type' => $a->type,
                'name' => $a->name ?? $a->code,
                'is_required' => (bool) $a->is_required,
                'is_unique' => (bool) $a->is_unique,
                'value_per_locale' => (bool) $a->value_per_locale,
                'value_per_channel' => (bool) $a->value_per_channel,
            ])->values()->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filters' => $this->filtersSchema(
                $schema,
                'List of filters: [{field, operator, value}].',
                'An attribute column (e.g. code, type, is_required, is_filterable) or the translated name.',
            ),
            'limit' => $schema->integer()
                ->description('Number of results per page.')
                ->default(25),
            'cursor' => $schema->string()
                ->description('Pagination cursor.'),
        ];
    }
}
