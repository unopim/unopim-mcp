<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\AdminApi\Http\Controllers\API\Catalog\ProductController;
use Webkul\MCP\Tools\BaseMcpTool;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Validator\ProductValuesValidator;

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
            'products'                       => ['required', 'array', 'min:1', 'max:50'],
            'products.*.sku'                 => ['required', 'string', 'max:100'],
            'products.*.type'                => ['nullable', 'string', 'in:simple,configurable,virtual,downloadable,bundle,grouped'],
            'products.*.attribute_family_id' => ['nullable', 'integer'],
            'products.*.values'              => ['nullable', 'array'],
        ]);

        DB::beginTransaction();

        try {
            $results = [];

            foreach ($validated['products'] as $productData) {
                $sku = $productData['sku'];

                $product = $this->productRepository->findOneByField('sku', $sku);

                if ($product) {
                    $product = $this->applyValues($product, $productData);

                    $results[] = ['id' => $product->id, 'sku' => $product->sku, 'action' => 'updated'];
                } else {
                    if (empty($productData['type']) || empty($productData['attribute_family_id'])) {
                        return Response::error("Product [{$sku}] does not exist. Both 'type' and 'attribute_family_id' are required for creation.");
                    }

                    $product = $this->productRepository->create([
                        'type'                => $productData['type'],
                        'attribute_family_id' => $productData['attribute_family_id'],
                        'sku'                 => $productData['sku'],
                    ]);

                    if (! empty($productData['values'])) {
                        $product = $this->applyValues($product, $productData);
                    }

                    $results[] = ['id' => $product->id, 'sku' => $product->sku, 'action' => 'created'];
                }
            }

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();

            return Response::error(json_encode([
                'message' => 'The submitted values are not valid; nothing was written.',
                'errors'  => $e->validator->errors()->messages(),
            ]));
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return Response::json(['success' => true, 'results' => $results]);
    }

    /**
     * Write values through the same preparation the Admin REST API performs.
     *
     * Handing the payload straight to ProductRepository::update() skipped three
     * things the controller does, and each failed silently while the tool still
     * reported success:
     *
     *  - values replaced the stored ones instead of merging, so
     *    AbstractType::update() resynced product_associations from a payload
     *    that never carried them and emptied the links;
     *  - option codes were never checked, so a value matching no attribute
     *    option was accepted and stored;
     *  - locale_specific was written for the current scope only, dropping every
     *    other locale in the same payload.
     *
     * validateOnlyExistingSectionData() and patchProduct() are what
     * SimpleProductController::partialUpdate() uses, so the tool and the REST
     * endpoint now agree.
     */
    protected function applyValues($product, array $productData)
    {
        if (! empty($productData['values'])) {
            $this->valuesValidator->validateOnlyExistingSectionData(
                data: $productData['values'],
                productId: $product->id,
            );
        }

        Event::dispatch('catalog.product.update.before', $product->id);

        return $this->productController->patchProduct($product, $productData);
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
                        'sku'                 => $schema->string()->description('The product SKU. Used as unique identifier.')->required(),
                        'type'                => $schema->string()->description('Product type (required for creation).'),
                        'attribute_family_id' => $schema->integer()->description('The attribute family ID (required for creation).'),
                        'values'              => $schema->object()->description('Map of attribute values.'),
                    ])
                ),
        ];
    }
}
