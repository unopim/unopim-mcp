<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\AdminApi\Http\Controllers\API\Catalog\ProductController;
use Webkul\MCP\Tools\BaseMcpTool;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\ProductValuesValidator;

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
        protected ProductRepository $productRepository,
        protected ProductController $productController,
        protected ProductValuesValidator $valuesValidator,
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
                    $product = $this->applyValues($product, $productData['values'] ?? []);

                    $results[] = ['id' => $product->id, 'sku' => $product->sku, 'action' => 'updated'];

                    continue;
                }

                if (empty($productData['type']) || empty($productData['attribute_family_id'])) {
                    DB::rollBack();

                    return Response::error("Product [{$sku}] does not exist. Both 'type' and 'attribute_family_id' are required for creation.");
                }

                $product = $this->productRepository->create([
                    'type' => $productData['type'],
                    'attribute_family_id' => $productData['attribute_family_id'],
                    'sku' => $sku,
                ]);

                $product = $this->applyValues($product, $productData['values'] ?? []);

                $results[] = ['id' => $product->id, 'sku' => $product->sku, 'action' => 'created'];
            }

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();

            return Response::error(json_encode([
                'message' => trans('mcp::app.tools.upsert-products.invalid-values'),
                'errors' => $e->validator->errors()->messages(),
            ]));
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return Response::json(['success' => true, 'results' => $results]);
    }

    /**
     * Write values through the same validate-then-merge path as the REST PATCH endpoint,
     * so stored values, associations and other locales survive a partial payload.
     *
     * @throws ValidationException
     */
    protected function applyValues(Product $product, array $values): Product
    {
        if (empty($values)) {
            return $product;
        }

        $this->valuesValidator->validateOnlyExistingSectionData(data: $values, productId: $product->id);

        Event::dispatch('catalog.product.update.before', $product->id);

        return $this->productController->patchProduct($product, [AbstractType::PRODUCT_VALUES_KEY => $values]);
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
