<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;
use Webkul\Product\Repositories\ProductRepository;

class ProductSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search for products in the UnoPim catalog using generic filters and pagination.';

    /**
     * The tool's name.
     */
    public string $name = 'search_products';

    public function __construct(
        protected ProductRepository $productRepository,
        protected UnoPimQueryBuilder $queryBuilder
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'filters' => ['nullable', 'array'],
            'limit'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'  => ['nullable', 'string'],
        ]);

        $query = $this->productRepository->getModel()->query();

        if (! empty($validated['filters'])) {
            $this->queryBuilder->applyFilters($query, $validated['filters']);
        }

        $paginator = $this->queryBuilder->paginate(
            $query->orderByDesc('id'),
            (int) ($validated['limit'] ?? 25),
            $validated['cursor'] ?? null
        );

        return Response::json([
            'count'       => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more'    => $paginator->hasMorePages(),
            'products'    => $paginator->map(fn ($p) => [
                'id'                  => $p->id,
                'sku'                 => $p->sku,
                'type'                => $p->type,
                'status'              => $p->status,
                'attribute_family_id' => $p->attribute_family_id,
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
                ->description('List of filters: [{field, operator, value}]. Supported operators: =, !=, IN, NOT IN, CONTAINS, STARTS WITH, ENDS WITH, >, <.')
                ->items(
                    $schema->object([
                        'field'    => $schema->string()->description('The field to filter by (e.g., sku, status).'),
                        'operator' => $schema->string()->description('The comparison operator.'),
                        'value'    => $schema->string()->description('The value to compare against. For IN and NOT IN, pass a comma-separated list ("a,b") or an array.'),
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
