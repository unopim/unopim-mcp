<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Locale;
use Webkul\MCP\Servers\UnoPimAgentServer;
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
