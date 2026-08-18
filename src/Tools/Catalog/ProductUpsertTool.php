<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\MCP\Tools\BaseMcpTool;
use Webkul\Product\Repositories\ProductRepository;

#[IsDestructive]
#[IsIdempotent]
class ProductUpsertTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more products in the UnoPim catalog. Automatically determines create vs update based on SKU/ID existence.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_products';

    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'products' => ['required', 'array', 'min:1', 'max:50'],
            'products.*.sku' => ['required', 'string', 'max:100'],
            'products.*.type' => ['nullable', 'string', 'in:simple,configurable,virtual,downloadable,bundle,grouped'],
            'products.*.attribute_family_id' => ['nullable', 'integer'],
            'products.*.values' => ['nullable', 'array'],
        ]);

        DB::beginTransaction();

        try {
            $results = [];

            foreach ($validated['products'] as $productData) {
                $sku = $productData['sku'];

                $product = $this->productRepository->findOneByField('sku', $sku);

                if ($product) {
                    $updateData = [];
                    if (isset($productData['values'])) {
                        $updateData['values'] = $productData['values'];
                    }

                    if (! empty($updateData)) {
                        $product = $this->productRepository->update($updateData, $product->id);
                    }

                    $results[] = ['id' => $product->id, 'sku' => $product->sku, 'action' => 'updated'];
                } else {
                    if (empty($productData['type']) || empty($productData['attribute_family_id'])) {
                        return Response::error("Product [{$sku}] does not exist. Both 'type' and 'attribute_family_id' are required for creation.");
                    }

                    $product = $this->productRepository->create([
                        'type' => $productData['type'],
                        'attribute_family_id' => $productData['attribute_family_id'],
                        'sku' => $productData['sku'],
                    ]);

                    if (! empty($productData['values'])) {
                        $product = $this->productRepository->update([
                            'values' => $productData['values'],
                        ], $product->id);
                    }

                    $results[] = ['id' => $product->id, 'sku' => $product->sku, 'action' => 'created'];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return Response::json(['success' => true, 'results' => $results]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'products' => $schema->array()
                ->description('Array of products to create or update (max 50).')
                ->items(
                    $schema->object([
                        'sku' => $schema->string()->description('The product SKU. Used as unique identifier.')->required(),
                        'type' => $schema->string()->description('Product type (required for creation).'),
                        'attribute_family_id' => $schema->integer()->description('The attribute family ID (required for creation).'),
                        'values' => $schema->object()->description('Map of attribute values.'),
                    ])
                ),
        ];
    }
}
