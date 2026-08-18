<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\MCP\Tools\BaseMcpTool;
use Webkul\Product\Repositories\ProductRepository;

class ProductGetTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Retrieve full details for a specific product by its identifier (ID or SKU).';

    /**
     * The tool's name.
     */
    public string $name = 'get_product';

    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $identifier = $validated['identifier'];

        $product = $this->productRepository->where('id', $identifier)
            ->orWhere('sku', $identifier)
            ->first();

        if (! $product) {
            return Response::error("Product with identifier '{$identifier}' not found.");
        }

        // Load relationships for a rich response
        $product->load([
            'attribute_family',
            'parent',
            'super_attributes',
            'variants',
        ]);

        return Response::json([
            'id' => $product->id,
            'sku' => $product->sku,
            'type' => $product->type,
            'status' => (bool) $product->status,
            'attribute_family' => [
                'id' => $product->attribute_family?->id,
                'name' => $product->attribute_family?->name,
                'code' => $product->attribute_family?->code,
            ],
            'values' => $product->values,
            'completeness' => $product->getCompletenessScore(),
            'parent_id' => $product->parent_id,
            'variants' => $product->variants->map(fn ($v) => ['id' => $v->id, 'sku' => $v->sku])->values()->all(),
            'created_at' => $product->created_at?->toDateTimeString(),
            'updated_at' => $product->updated_at?->toDateTimeString(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'identifier' => $schema->string()
                ->description('The product ID or SKU.'),
        ];
    }
}
