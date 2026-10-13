<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Catalog\ProductGetTool;
use Webkul\MCP\Tools\Catalog\ProductSearchTool;
use Webkul\MCP\Tools\Catalog\ProductUpsertTool;
use Webkul\Product\Models\Product;

function attachAttributeToFamily(Product $product, array $attributes): Attribute
{
    $attribute = Attribute::factory()->create($attributes);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    return $attribute;
}

it('keeps existing associations when a partial update omits them', function () {
    $product = Product::factory()->simple()->create();
    $related = Product::factory()->simple()->create();

    $product->values = array_merge($product->values ?? [], [
        'associations' => ['related_products' => [$related->sku]],
    ]);
    $product->save();
    $product->getTypeInstance()->syncAssociationLinks($product, $product->values);

    $attribute = attachAttributeToFamily($product, ['type' => 'text', 'value_per_locale' => false, 'value_per_channel' => false]);

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [[
            'sku' => $product->sku,
            'values' => ['common' => [$attribute->code => 'Partial update']],
        ]],
    ])->assertOk()->assertSee('updated');

    $product->refresh();

    expect($product->values['associations']['related_products'] ?? [])->toBe([$related->sku])
        ->and($product->values['common'][$attribute->code] ?? null)->toBe('Partial update')
        ->and(DB::table('product_associations')->where('product_id', $product->id)->count())->toBe(1);
});

it('rejects an option code that does not exist and writes nothing', function () {
    $product = Product::factory()->simple()->create();
    $attribute = attachAttributeToFamily($product, ['type' => 'select']);
    $before = $product->values;

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [[
            'sku' => $product->sku,
            'values' => ['common' => [$attribute->code => 'does_not_exist_'.uniqid()]],
        ]],
    ])->assertHasErrors();

    expect($product->refresh()->values)->toBe($before);
});

it('stores every locale named in a locale specific payload', function () {
    $product = Product::factory()->simple()->create();
    $attribute = attachAttributeToFamily($product, ['type' => 'text', 'value_per_locale' => true, 'value_per_channel' => false]);

    $channel = core()->getDefaultChannel();

    if ($channel->locales->count() < 2) {
        $channel->locales()->attach(Locale::whereNotIn('id', $channel->locales->pluck('id'))->firstOrFail());
        $channel->load('locales');
    }

    $locales = $channel->locales->take(2)->pluck('code')->all();

    $data = collect($locales)->mapWithKeys(fn (string $locale): array => [$locale => [$attribute->code => 'Value '.$locale]])->all();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [[
            'sku' => $product->sku,
            'values' => ['locale_specific' => $data],
        ]],
    ])->assertOk();

    $stored = $product->refresh()->values['locale_specific'] ?? [];

    foreach ($data as $locale => $values) {
        expect($stored[$locale][$attribute->code] ?? null)->toBe($values[$attribute->code]);
    }
});

it('validates values on create and rolls the new product back', function () {
    $family = Product::factory()->simple()->create()->attribute_family;
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);
    $sku = 'MCP-INVALID-'.uniqid();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [[
            'sku' => $sku,
            'type' => 'simple',
            'attribute_family_id' => $family->id,
            'values' => ['common' => [$attribute->code => 'does_not_exist_'.uniqid()]],
        ]],
    ])->assertHasErrors();

    expect(Product::where('sku', $sku)->exists())->toBeFalse();
});

function configurableFamilyWithAxis(): array
{
    $family = Product::factory()->simple()->create()->attribute_family;

    $axis = Attribute::factory()->create(['type' => 'select', 'value_per_locale' => false, 'value_per_channel' => false]);

    $family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($axis);

    foreach (['red', 'blue'] as $index => $code) {
        AttributeOption::factory()->create(['attribute_id' => $axis->id, 'code' => $code, 'sort_order' => $index + 1]);
    }

    return [$family, $axis];
}

it('rejects product types that are not registered in unopim', function () {
    $family = Product::factory()->simple()->create()->attribute_family;
    $sku = 'MCP-VIRTUAL-'.uniqid();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $sku, 'type' => 'virtual', 'attribute_family_id' => $family->id]],
    ])->assertHasErrors()->assertDontSee('unexpected error');

    expect(Product::where('sku', $sku)->exists())->toBeFalse();
});

it('rejects an attribute family that does not exist', function () {
    $sku = 'MCP-NOFAM-'.uniqid();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $sku, 'type' => 'simple', 'attribute_family_id' => 999999999]],
    ])->assertHasErrors()->assertDontSee('unexpected error');

    expect(Product::where('sku', $sku)->exists())->toBeFalse();
});

it('rejects a sku that breaks the unopim sku rule', function () {
    $family = Product::factory()->simple()->create()->attribute_family;
    $sku = 'MCP,BAD-'.uniqid();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $sku, 'type' => 'simple', 'attribute_family_id' => $family->id]],
    ])->assertHasErrors();

    expect(Product::where('sku', $sku)->exists())->toBeFalse();
});

it('requires super attributes to create a configurable product', function () {
    [$family] = configurableFamilyWithAxis();
    $sku = 'MCP-CONF-NOAXIS-'.uniqid();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $sku, 'type' => 'configurable', 'attribute_family_id' => $family->id]],
    ])->assertHasErrors()->assertDontSee('unexpected error');

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $sku, 'type' => 'configurable', 'attribute_family_id' => $family->id, 'super_attributes' => ['sku']]],
    ])->assertHasErrors()->assertSee('[sku] must be select attributes');

    expect(Product::where('sku', $sku)->exists())->toBeFalse();
});

it('creates a configurable product and a variant under it by parent sku', function () {
    [$family, $axis] = configurableFamilyWithAxis();
    $parentSku = 'MCP-CONF-'.uniqid();
    $variantSku = $parentSku.'-RED';

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            ['sku' => $parentSku, 'type' => 'configurable', 'attribute_family_id' => $family->id, 'super_attributes' => [$axis->code]],
            ['sku' => $variantSku, 'parent_sku' => $parentSku, 'values' => ['common' => [$axis->code => 'red']]],
        ],
    ])->assertOk()->assertSee('created');

    $parent = Product::where('sku', $parentSku)->firstOrFail();
    $variant = Product::where('sku', $variantSku)->firstOrFail();

    expect($parent->type)->toBe('configurable')
        ->and($parent->super_attributes->pluck('code')->all())->toBe([$axis->code])
        ->and($variant->parent_id)->toBe($parent->id)
        ->and($variant->type)->toBe('simple')
        ->and($variant->values['common'][$axis->code] ?? null)->toBe('red');

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $parentSku.'-DUP', 'parent_sku' => $parentSku, 'values' => ['common' => [$axis->code => 'red']]]],
    ])->assertHasErrors()->assertSee('already has a variant');

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $parentSku.'-NOAXIS', 'parent_sku' => $parentSku]],
    ])->assertHasErrors()->assertSee('needs a valid option');

    expect(Product::where('parent_id', $parent->id)->count())->toBe(1);

    $variant->delete();
    $parent->delete();
});

it('writes the status column and reports it as a boolean', function () {
    $product = Product::factory()->simple()->create(['status' => 1]);

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $product->sku, 'status' => false]],
    ])->assertOk()->assertSee('updated');

    expect((bool) $product->refresh()->status)->toBeFalse();

    $search = $this->toolPayload(ProductSearchTool::class, [
        'filters' => [['field' => 'sku', 'operator' => '=', 'value' => $product->sku]],
    ]);

    expect($search['products'][0]['status'] ?? null)->toBeFalse()
        ->and($this->toolPayload(ProductGetTool::class, ['identifier' => $product->sku])['status'] ?? null)->toBeFalse();
});

it('rejects updating a variant to variant attribute values a sibling already has', function () {
    [$family, $axis] = configurableFamilyWithAxis();
    $parentSku = 'MCP-CONF-SIB-'.uniqid();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            ['sku' => $parentSku, 'type' => 'configurable', 'attribute_family_id' => $family->id, 'super_attributes' => [$axis->code]],
            ['sku' => $parentSku.'-RED', 'parent_sku' => $parentSku, 'values' => ['common' => [$axis->code => 'red']]],
            ['sku' => $parentSku.'-BLUE', 'parent_sku' => $parentSku, 'values' => ['common' => [$axis->code => 'blue']]],
        ],
    ])->assertOk();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $parentSku.'-BLUE', 'values' => ['common' => [$axis->code => 'red']]]],
    ])->assertHasErrors()->assertSee($parentSku.'-BLUE')->assertSee('products.0.values.common.'.$axis->code);

    expect(Product::where('sku', $parentSku.'-BLUE')->firstOrFail()->values['common'][$axis->code] ?? null)->toBe('blue');

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $parentSku.'-BLUE', 'values' => ['common' => [$axis->code => 'blue']]]],
    ])->assertOk()->assertSee('updated');

    Product::where('sku', 'like', $parentSku.'%')->orderByDesc('id')->get()->each->delete();
});

it('names the index and sku of the item that failed in a batch and writes nothing', function () {
    $first = Product::factory()->simple()->create();
    $second = Product::factory()->simple()->create();
    $attribute = attachAttributeToFamily($second, ['type' => 'select', 'value_per_locale' => false, 'value_per_channel' => false]);
    $text = attachAttributeToFamily($first, ['type' => 'text', 'value_per_locale' => false, 'value_per_channel' => false]);

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            ['sku' => $first->sku, 'values' => ['common' => [$text->code => 'Batch first']]],
            ['sku' => $second->sku, 'values' => ['common' => [$attribute->code => 'does_not_exist_'.uniqid()]]],
        ],
    ])->assertHasErrors()
        ->assertSee('products.1.values.common.'.$attribute->code)
        ->assertSee($second->sku);

    expect($first->refresh()->values['common'][$text->code] ?? null)->toBeNull();
});

function priceAttributeOn(Product $product): Attribute
{
    return attachAttributeToFamily($product, ['type' => 'price', 'value_per_locale' => false, 'value_per_channel' => false]);
}

it('rejects a price in a currency that is not active', function () {
    $product = Product::factory()->simple()->create();
    $price = priceAttributeOn($product);
    $inactive = Currency::firstOrCreate(['code' => 'XTS'], ['status' => 0]);
    $inactive->update(['status' => 0]);
    $active = Currency::where('status', 1)->value('code');

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $product->sku, 'values' => ['common' => [$price->code => ['XTS' => '1.00']]]]],
    ])->assertHasErrors()->assertSee('XTS')->assertSee('products.0.values.common.'.$price->code);

    expect($product->refresh()->values['common'][$price->code] ?? null)->toBeNull();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $product->sku, 'values' => ['common' => [$price->code => [$active => '19.99']]]]],
    ])->assertOk();

    expect($product->refresh()->values['common'][$price->code][$active] ?? null)->toBe('19.99');

    $inactive->delete();
});

it('points a misplaced value to the shape it belongs in', function () {
    $product = Product::factory()->simple()->create();
    $price = priceAttributeOn($product);
    $localized = attachAttributeToFamily($product, ['type' => 'text', 'value_per_locale' => true, 'value_per_channel' => false]);
    $category = Category::factory()->create();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $product->sku, 'values' => ['common' => [$price->code => ['19.99']]]]],
    ])->assertHasErrors()->assertSee('products.0.values.common.'.$price->code)->assertSee('USD');

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $product->sku, 'values' => ['common' => ['categories' => [$category->code]]]]],
    ])->assertHasErrors()->assertSee('products.0.values.common.categories')->assertSee('values.categories');

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [['sku' => $product->sku, 'values' => ['common' => [$localized->code => 'Hello']]]],
    ])->assertHasErrors()->assertSee('values.locale_specific');
});

it('documents the values shape in the input schema', function () {
    $description = json_encode(app(ProductUpsertTool::class)->toArray());

    expect($description)
        ->toContain('locale_specific')
        ->toContain('channel_locale_specific')
        ->toContain('categories')
        ->toContain('associations');
});
