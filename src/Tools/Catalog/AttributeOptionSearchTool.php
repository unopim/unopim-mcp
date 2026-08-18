<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;

class AttributeOptionSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search for attribute options (e.g., colors, sizes) in the UnoPim catalog using filters and pagination.';

    /**
     * The tool's name.
     */
    public string $name = 'search_attribute_options';

    public function __construct(
        protected AttributeOptionRepository $optionRepository,
        protected UnoPimQueryBuilder $queryBuilder
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'filters' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = $this->optionRepository->getModel()->query();

        if (! empty($validated['filters'])) {
            $this->queryBuilder->applyFilters($query, $validated['filters']);
        }

        $paginator = $this->queryBuilder->paginate(
            $query->orderBy('sort_order'),
            (int) ($validated['limit'] ?? 25),
            $validated['cursor'] ?? null
        );

        return Response::json([
            'count' => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'options' => $paginator->map(fn ($o) => [
                'id' => $o->id,
                'attribute_id' => $o->attribute_id,
                'code' => $o->code,
                'label' => $o->label,
                'sort_order' => $o->sort_order,
                'swatch_value' => $o->swatch_value,
            ])->values()->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filters' => $schema->array()
                ->description('List of filters: [{field, operator, value}]. Supported fields: attribute_id, code, label.')
                ->items(
                    $schema->object([
                        'field' => $schema->string()->description('The field to filter by (e.g., attribute_id, code).'),
                        'operator' => $schema->string()->description('The comparison operator (=, !=, IN, CONTAINS, etc.).'),
                        'value' => $schema->string()->description('The value to compare against.'),
                    ])
                ),
            'limit' => $schema->integer()
                ->description('Number of results per page (max 100).')
                ->default(25),
            'cursor' => $schema->string()
                ->description('Pagination cursor.'),
        ];
    }
}
