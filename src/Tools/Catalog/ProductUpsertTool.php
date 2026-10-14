<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\AdminApi\Http\Controllers\API\Catalog\ProductController;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Rules\Sku;
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
     * The configurable product type key; a literal because the ProductType enum only exists from UnoPim 3.1.3.
     */
    protected const CONFIGURABLE = 'configurable';

    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more products in the UnoPim catalog. Automatically determines create vs update based on SKU existence. Supports configurable products (super_attributes) and their variants (parent_sku). Each attribute value goes in the values section that matches the attribute\'s value_per_locale and value_per_channel flags (see search_attributes). The batch is all-or-nothing: an error names the failing item as products.{index} and its SKU.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_products';

    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductController $productController,
        protected ProductValuesValidator $valuesValidator,
        protected AttributeFamilyRepository $familyRepository,
        protected AttributeOptionRepository $optionRepository,
        protected AttributeService $attributeService,
        protected CurrencyRepository $currencyRepository,
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'products' => ['required', 'array', 'min:1', 'max:50'],
            'products.*.sku' => ['required', 'string', new Sku],
            'products.*.type' => ['nullable', 'string', Rule::in($this->creatableTypes())],
            'products.*.attribute_family_id' => ['nullable', 'integer', Rule::exists($this->familyRepository->getModel()->getTable(), 'id')],
            'products.*.parent_sku' => ['nullable', 'string'],
            'products.*.super_attributes' => ['nullable', 'array'],
            'products.*.super_attributes.*' => ['string'],
            'products.*.status' => ['nullable', 'boolean'],
            'products.*.values' => ['nullable', 'array'],
        ]);

        DB::beginTransaction();

        try {
            $results = [];

            foreach ($validated['products'] as $index => $productData) {
                try {
                    $results[] = $this->upsert($productData, $index);
                } catch (ValidationException $e) {
                    DB::rollBack();

                    return $this->invalidProduct($e, $index, $productData['sku']);
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
     * Create or update one product of the batch.
     *
     * @return array{id: int, sku: string, action: string}
     *
     * @throws ValidationException
     */
    protected function upsert(array $productData, int $index): array
    {
        $sku = $productData['sku'];
        $values = $productData['values'] ?? [];
        $status = isset($productData['status']) ? (bool) $productData['status'] : null;
        $parentSku = $productData['parent_sku'] ?? null;

        $this->assertValuesShape($values);

        $product = $this->productRepository->findOneByField('sku', $sku);

        if ($product) {
            if (filled($parentSku) && $product->parent?->sku !== $parentSku) {
                $this->fail("products.{$index}.parent_sku", 'parent-immutable', ['sku' => $sku]);
            }

            $product = $this->applyChanges($product, $values, $status);

            return ['id' => $product->id, 'sku' => $product->sku, 'action' => 'updated'];
        }

        if (filled($parentSku)) {
            $product = $this->createVariant($parentSku, $sku, $values, $index);
        } else {
            if (empty($productData['type']) || empty($productData['attribute_family_id'])) {
                $this->fail("products.{$index}.type", 'product-create-incomplete', ['sku' => $sku]);
            }

            $product = $this->productRepository->create(array_filter([
                'type' => $productData['type'],
                'attribute_family_id' => $productData['attribute_family_id'],
                'sku' => $sku,
                'super_attributes' => $productData['type'] === self::CONFIGURABLE
                    ? $this->superAttributes($productData, $index)
                    : null,
            ], fn (mixed $value): bool => $value !== null));
        }

        $product = $this->applyChanges($product, $values, $status);

        return ['id' => $product->id, 'sku' => $product->sku, 'action' => 'created'];
    }

    /**
     * Report the batch item that failed, with its errors keyed under products.{index} so a
     * caller of a large batch can tell which product to fix.
     */
    protected function invalidProduct(ValidationException $e, int $index, string $sku): Response
    {
        $errors = collect($e->validator->errors()->messages())
            ->mapWithKeys(fn (array $messages, string $key): array => [
                str_starts_with($key, 'products.') ? $key : "products.{$index}.values.{$key}" => $messages,
            ])
            ->all();

        return Response::error(json_encode([
            'message' => trans('mcp::app.tools.upsert-products.invalid-values', ['sku' => $sku, 'index' => $index]),
            'index' => $index,
            'sku' => $sku,
            'errors' => $errors,
        ]));
    }

    /**
     * Point a value sent in the wrong place to where it belongs, and reject prices that are not
     * keyed by active currency codes, before the values reach UnoPim's validator.
     *
     * @throws ValidationException
     */
    protected function assertValuesShape(array $values): void
    {
        $prices = [];

        foreach ($this->attributeValues($values) as [$section, $path, $code, $value]) {
            if (in_array($code, [AbstractType::CATEGORY_VALUES_KEY, AbstractType::ASSOCIATION_VALUES_KEY], true)) {
                $this->fail("{$path}.{$code}", 'values-section-misplaced', ['key' => $code, 'section' => $section]);
            }

            $attribute = is_string($code) ? $this->attributeService->findAttributeByCode($code) : null;

            if (! $attribute instanceof Attribute) {
                continue;
            }

            $expected = $this->sectionFor($attribute);

            if ($expected !== $section) {
                $this->fail("{$path}.{$code}", 'values-attribute-section', [
                    'code' => $code,
                    'path' => $this->examplePath($expected, $code),
                ]);
            }

            if ($attribute->type !== Attribute::PRICE_FIELD_TYPE || $value === null) {
                continue;
            }

            if (! is_array($value) || ($value !== [] && array_is_list($value))) {
                $this->fail("{$path}.{$code}", 'values-price-shape', ['code' => $code]);
            }

            $prices["{$path}.{$code}"] = array_map(strval(...), array_keys($value));
        }

        if ($prices === []) {
            return;
        }

        $active = $this->currencyRepository->getActiveCurrencies()->pluck('code')->all();

        foreach ($prices as $key => $currencies) {
            $inactive = array_diff($currencies, $active);

            if ($inactive !== []) {
                $this->fail($key, 'values-currency-inactive', [
                    'currencies' => implode(', ', $inactive),
                    'active' => implode(', ', $active),
                ]);
            }
        }
    }

    /**
     * Flatten the attribute sections of a values payload into [section, path, code, value] rows,
     * skipping anything malformed so UnoPim's validator reports it.
     *
     * @return \Generator<int, array{0: string, 1: string, 2: int|string, 3: mixed}>
     */
    protected function attributeValues(array $values): \Generator
    {
        foreach ($this->scopedValues($values) as [$section, $path, $attributes]) {
            if (! is_array($attributes)) {
                continue;
            }

            foreach ($attributes as $code => $value) {
                yield [$section, $path, $code, $value];
            }
        }
    }

    /**
     * The attribute maps of each section with their dotted paths.
     *
     * @return \Generator<int, array{0: string, 1: string, 2: mixed}>
     */
    protected function scopedValues(array $values): \Generator
    {
        if (isset($values[AbstractType::COMMON_VALUES_KEY])) {
            yield [AbstractType::COMMON_VALUES_KEY, AbstractType::COMMON_VALUES_KEY, $values[AbstractType::COMMON_VALUES_KEY]];
        }

        foreach ([AbstractType::LOCALE_VALUES_KEY, AbstractType::CHANNEL_VALUES_KEY] as $section) {
            foreach ((array) ($values[$section] ?? []) as $scope => $attributes) {
                yield [$section, "{$section}.{$scope}", $attributes];
            }
        }

        $section = AbstractType::CHANNEL_LOCALE_VALUES_KEY;

        foreach ((array) ($values[$section] ?? []) as $channel => $locales) {
            foreach ((array) $locales as $locale => $attributes) {
                yield [$section, "{$section}.{$channel}.{$locale}", $attributes];
            }
        }
    }

    /**
     * The values section an attribute's value belongs in.
     */
    protected function sectionFor(Attribute $attribute): string
    {
        return match (true) {
            $attribute->isLocaleAndChannelBasedAttribute() => AbstractType::CHANNEL_LOCALE_VALUES_KEY,
            $attribute->isChannelBasedAttribute() => AbstractType::CHANNEL_VALUES_KEY,
            $attribute->isLocaleBasedAttribute() => AbstractType::LOCALE_VALUES_KEY,
            default => AbstractType::COMMON_VALUES_KEY,
        };
    }

    /**
     * Where an attribute's value goes, as a path with channel and locale placeholders.
     */
    protected function examplePath(string $section, string $code): string
    {
        return match ($section) {
            AbstractType::CHANNEL_LOCALE_VALUES_KEY => "values.{$section}.{channel}.{locale}.{$code}",
            AbstractType::CHANNEL_VALUES_KEY => "values.{$section}.{channel}.{$code}",
            AbstractType::LOCALE_VALUES_KEY => "values.{$section}.{locale}.{$code}",
            default => "values.{$section}.{$code}",
        };
    }

    /**
     * Product types a client may create directly; internal types such as variant groups are excluded.
     *
     * @return array<int, string>
     */
    protected function creatableTypes(): array
    {
        return collect(config('product_types', []))
            ->reject(fn (array $type): bool => (bool) ($type['internal'] ?? false))
            ->keys()
            ->all();
    }

    /**
     * Validate a configurable product's super attributes against its family, as the REST API does.
     *
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    protected function superAttributes(array $productData, int $index): array
    {
        $codes = array_values(array_unique($productData['super_attributes'] ?? []));

        if ($codes === []) {
            throw ValidationException::withMessages([
                "products.{$index}.super_attributes" => trans('validation.required', ['attribute' => 'super_attributes']),
            ]);
        }

        $family = $this->familyRepository->find($productData['attribute_family_id']);

        $invalid = array_diff($codes, $family->getConfigurableAttributes()->pluck('code')->all());

        if ($invalid !== []) {
            $this->fail("products.{$index}.super_attributes", 'super-attributes-invalid', [
                'attributes' => implode(', ', $invalid),
                'family' => $family->code,
            ]);
        }

        return $codes;
    }

    /**
     * Create a simple variant under a configurable parent from the parent's variant attribute values in `values.common`.
     *
     * @throws ValidationException
     */
    protected function createVariant(string $parentSku, string $sku, array $values, int $index): Product
    {
        $parent = $this->productRepository->findOneByField('sku', $parentSku);

        if (! $parent || $parent->type !== self::CONFIGURABLE) {
            $this->fail("products.{$index}.parent_sku", 'parent-not-found', ['sku' => $parentSku]);
        }

        if ((int) ($parent->variantStructure?->levels ?? 1) === 2) {
            $this->fail("products.{$index}.parent_sku", 'parent-unsupported', ['sku' => $parentSku]);
        }

        $superAttributes = $parent->super_attributes;
        $common = $values[AbstractType::COMMON_VALUES_KEY] ?? [];
        $axis = [];

        foreach ($superAttributes as $attribute) {
            $option = $common[$attribute->code] ?? null;

            $exists = is_string($option) && $this->optionRepository->findOneWhere([
                'attribute_id' => $attribute->id,
                'code' => $option,
            ]);

            if (! $exists) {
                $this->fail("products.{$index}.values", 'variant-axis-invalid', [
                    'sku' => $sku,
                    'attributes' => $superAttributes->pluck('code')->implode(', '),
                ]);
            }

            $axis[$attribute->code] = $option;
        }

        if (! $this->productRepository->isUniqueVariantForProduct($parent->id, $axis, $sku)) {
            $this->fail("products.{$index}.sku", 'variant-exists', ['parent' => $parentSku, 'sku' => $sku]);
        }

        return $parent->getTypeInstance()->createVariant($parent, $superAttributes, [
            'sku' => $sku,
            AbstractType::PRODUCT_VALUES_KEY => [AbstractType::COMMON_VALUES_KEY => $axis],
        ]);
    }

    /**
     * Write values and status through the same validate-then-merge path as the REST PATCH endpoint,
     * so stored values, associations and other locales survive a partial payload.
     *
     * @throws ValidationException
     */
    protected function applyChanges(Product $product, array $values, ?bool $status): Product
    {
        $changes = [];

        if ($values !== []) {
            $this->valuesValidator->validateOnlyExistingSectionData(data: $values, productId: $product->id);

            $this->assertUniqueVariantAxis($product, $values[AbstractType::COMMON_VALUES_KEY] ?? []);

            $changes[AbstractType::PRODUCT_VALUES_KEY] = $values;
        }

        if ($status !== null) {
            $changes['status'] = $status;
        }

        if ($changes === []) {
            return $product;
        }

        Event::dispatch('catalog.product.update.before', $product->id);

        return $this->productController->patchProduct($product, $changes);
    }

    /**
     * Reject a change to a variant's variant attribute values that a sibling variant already has.
     *
     * @throws ValidationException
     */
    protected function assertUniqueVariantAxis(Product $product, mixed $common): void
    {
        $parent = $product->parent;

        if (! $parent || ! is_array($common)) {
            return;
        }

        $codes = $parent->super_attributes->pluck('code')->all();
        $changed = array_keys(array_filter(
            array_intersect_key($common, array_flip($codes)),
            fn (mixed $option, string $code): bool => $option !== ($product->values[AbstractType::COMMON_VALUES_KEY][$code] ?? null),
            ARRAY_FILTER_USE_BOTH,
        ));

        if ($changed === []) {
            return;
        }

        $axis = collect($codes)
            ->mapWithKeys(fn (string $code): array => [$code => $common[$code] ?? $product->values[AbstractType::COMMON_VALUES_KEY][$code] ?? null])
            ->all();

        if (! $this->productRepository->isUniqueVariantForProduct($parent->id, $axis, null, $product->id)) {
            $this->fail(AbstractType::COMMON_VALUES_KEY.'.'.$changed[0], 'variant-values-taken', [
                'sku' => $product->sku,
                'parent' => $parent->sku,
            ]);
        }
    }

    /**
     * Throw a localized validation error for one product.
     *
     * @throws ValidationException
     */
    protected function fail(string $key, string $message, array $replace = []): never
    {
        throw ValidationException::withMessages([$key => trans("mcp::app.tools.catalog-writes.{$message}", $replace)]);
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
                        'type' => $schema->string()->enum($this->creatableTypes())->description('Product type, required to create a product without parent_sku. Ignored on update.'),
                        'attribute_family_id' => $schema->integer()->description('The attribute family ID, required to create a product without parent_sku. Ignored on update.'),
                        'super_attributes' => $schema->array()->items($schema->string())->description('Codes of the variant attributes, required to create a configurable product. Each must be a select attribute of the family that does not vary by locale or channel.'),
                        'parent_sku' => $schema->string()->description('Create this product as a variant of the given configurable product. values.common must hold an option code for each of the parent\'s super attributes; the variant takes the parent\'s family.'),
                        'status' => $schema->boolean()->description('Whether the product is enabled.'),
                        'values' => $schema->object()->description(
                            'Attribute values by scope; sections not sent are kept. Put each attribute in the section that matches its value_per_locale and value_per_channel flags (see search_attributes): '
                            .'common.{attribute} (neither), locale_specific.{locale}.{attribute}, channel_specific.{channel}.{attribute}, channel_locale_specific.{channel}.{locale}.{attribute} (both). '
                            .'categories is a top-level list of non-root category codes; associations is a top-level object with related_products, up_sells and cross_sells lists of SKUs. '
                            .'Formats: text as a string; select as an option code; multiselect and checkbox as comma-separated option codes; boolean as "true" or "false"; date as YYYY-MM-DD; datetime as YYYY-MM-DD HH:MM:SS; '
                            .'price as an object of active currency code to amount, never a list. '
                            .'Example: {"common": {"color": "red"}, "channel_locale_specific": {"default": {"en_US": {"name": "Tee", "price": {"USD": "19.99"}}}}, "categories": ["shirts"], "associations": {"related_products": ["TEE-2"]}}'
                        ),
                    ])
                ),
        ];
    }
}
