<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;
use Webkul\Product\Repositories\ProductRepository;

#[IsReadOnly]
class ProductSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search for products in the UnoPim catalog using generic filters and pagination.';

    /**
     * The tool's title.
     */
    protected string $title = 'Search products';

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
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
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
            'count' => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'products' => $paginator->map(fn ($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'type' => $p->type,
                'status' => (bool) $p->status,
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
            'filters' => $this->filtersSchema(
                $schema,
                'List of filters: [{field, operator, value}]. Call get_catalog_schema for the fields that can be filtered.',
                'A product column (id, sku, type, status, attribute_family_id, parent_id, created_at, updated_at) or a JSON path into values, e.g. values->common->color.',
            ),
            'limit' => $schema->integer()
                ->description('Number of results per page (max 100).')
                ->default(25),
            'cursor' => $schema->string()
                ->description('Pagination cursor.'),
        ];
    }
}
