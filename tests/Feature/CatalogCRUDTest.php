<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Catalog\AttributeUpsertTool;
use Webkul\MCP\Tools\Catalog\CategoryUpsertTool;
use Webkul\MCP\Tools\Catalog\ProductGetTool;
use Webkul\MCP\Tools\Catalog\ProductUpsertTool;
use Webkul\Product\Models\Product;

it('creates and updates a product via upsert', function () {
    $family = AttributeFamily::first() ?? AttributeFamily::factory()->create();
    $sku = 'TEST-PROD-'.uniqid();

    // Create via upsert
    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            [
                'sku' => $sku,
                'type' => 'simple',
                'attribute_family_id' => $family->id,
            ],
        ],
    ])->assertOk()->assertSee($sku)->assertSee('created');

    $product = Product::where('sku', $sku)->first();
    expect($product)->not->toBeNull();

    // Update via upsert
    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            [
                'sku' => $sku,
                'values' => $this->productNameValues('Updated Name'),
            ],
        ],
    ])->assertOk()->assertSee('updated');

    // Get product details
    UnoPimAgentServer::tool(ProductGetTool::class, [
        'identifier' => $sku,
    ])->assertOk()->assertSee($sku);

    // Cleanup
    Product::where('sku', $sku)->delete();
});

it('creates and updates a category via upsert', function () {
    $code = 'test-cat-'.uniqid();

    // Create via upsert
    UnoPimAgentServer::tool(CategoryUpsertTool::class, [
        'categories' => [
            [
                'code' => $code,
                'additional_data' => ['common' => ['name' => 'Test Category']],
            ],
        ],
    ])->assertOk()->assertSee($code)->assertSee('created');

    $category = Category::where('code', $code)->first();
    expect($category)->not->toBeNull();

    // Update via upsert
    UnoPimAgentServer::tool(CategoryUpsertTool::class, [
        'categories' => [
            [
                'code' => $code,
                'additional_data' => ['common' => ['name' => 'Updated Category']],
            ],
        ],
    ])->assertOk()->assertSee('updated');

    // Cleanup
    Category::where('code', $code)->delete();
});

it('creates and updates an attribute via upsert', function () {
    $code = 'test_attr_'.uniqid();

    // Create via upsert
    UnoPimAgentServer::tool(AttributeUpsertTool::class, [
        'attributes' => [
            [
                'code' => $code,
                'type' => 'text',
                'name' => 'Test Attribute',
            ],
        ],
    ])->assertOk()->assertSee($code)->assertSee('created');

    $attribute = Attribute::where('code', $code)->first();
    expect($attribute)->not->toBeNull();

    // Update via upsert
    UnoPimAgentServer::tool(AttributeUpsertTool::class, [
        'attributes' => [
            [
                'code' => $code,
                'name' => 'Updated Attribute',
            ],
        ],
    ])->assertOk()->assertSee('updated');

    // Cleanup
    Attribute::where('code', $code)->delete();
});

it('upserts options by code when updating an existing select attribute', function () {
    $code = 'test_sel_'.uniqid();

    UnoPimAgentServer::tool(AttributeUpsertTool::class, [
        'attributes' => [[
            'code' => $code,
            'type' => 'select',
            'name' => 'Select',
            'options' => [['code' => 'red', 'label' => 'Red', 'sort_order' => 1]],
        ]],
    ])->assertOk()->assertSee('created');

    UnoPimAgentServer::tool(AttributeUpsertTool::class, [
        'attributes' => [[
            'code' => $code,
            'options' => [
                ['code' => 'red', 'label' => 'Crimson'],
                ['code' => 'blue', 'label' => 'Blue', 'sort_order' => 2],
            ],
        ]],
    ])->assertOk()->assertSee('updated');

    $attribute = Attribute::where('code', $code)->firstOrFail();
    $options = AttributeOption::where('attribute_id', $attribute->id)->get()->keyBy('code');

    expect($options->keys()->sort()->values()->all())->toBe(['blue', 'red'])
        ->and($options['red']->label)->toBe('Crimson')
        ->and($options['red']->sort_order)->toBe(1)
        ->and($options['blue']->label)->toBe('Blue');

    $attribute->delete();
});

it('rejects changing the type of an existing attribute', function () {
    $code = 'test_type_'.uniqid();

    UnoPimAgentServer::tool(AttributeUpsertTool::class, [
        'attributes' => [['code' => $code, 'type' => 'text', 'name' => 'Typed']],
    ])->assertOk();

    UnoPimAgentServer::tool(AttributeUpsertTool::class, [
        'attributes' => [['code' => $code, 'type' => 'select']],
    ])->assertHasErrors()->assertDontSee('unexpected error');

    expect(Attribute::where('code', $code)->value('type'))->toBe('text');

    Attribute::where('code', $code)->delete();
});

it('rejects an unknown attribute type and a malformed attribute code', function () {
    $code = 'test_bad_'.uniqid();

    UnoPimAgentServer::tool(AttributeUpsertTool::class, [
        'attributes' => [['code' => $code, 'type' => 'virtual_thing', 'name' => 'Bad']],
    ])->assertHasErrors();

    UnoPimAgentServer::tool(AttributeUpsertTool::class, [
        'attributes' => [['code' => 'bad code-'.uniqid(), 'type' => 'text', 'name' => 'Bad']],
    ])->assertHasErrors();

    expect(Attribute::where('code', $code)->exists())->toBeFalse();
});

it('stores the category name under the requested locale', function () {
    $code = 'test_cat_name_'.uniqid();
    $locale = core()->getRequestedLocaleCode();

    UnoPimAgentServer::tool(CategoryUpsertTool::class, [
        'categories' => [['code' => $code, 'name' => 'Shoes']],
    ])->assertOk()->assertSee('created');

    $category = Category::where('code', $code)->firstOrFail();

    expect($category->additional_data['locale_specific'][$locale]['name'] ?? null)->toBe('Shoes');

    UnoPimAgentServer::tool(CategoryUpsertTool::class, [
        'categories' => [['code' => $code, 'name' => 'Boots']],
    ])->assertOk()->assertSee('updated');

    expect($category->refresh()->additional_data['locale_specific'][$locale]['name'] ?? null)->toBe('Boots');

    $category->delete();
});

it('rejects a missing parent category', function () {
    $code = 'test_cat_orphan_'.uniqid();

    UnoPimAgentServer::tool(CategoryUpsertTool::class, [
        'categories' => [['code' => $code, 'name' => 'Orphan', 'parent_id' => 999999999]],
    ])->assertHasErrors()->assertDontSee('unexpected error');

    expect(Category::where('code', $code)->exists())->toBeFalse();
});

it('rejects moving a category under itself or its own descendant', function () {
    $parentCode = 'test_cat_p_'.uniqid();
    $childCode = 'test_cat_c_'.uniqid();

    UnoPimAgentServer::tool(CategoryUpsertTool::class, [
        'categories' => [['code' => $parentCode, 'name' => 'Parent']],
    ])->assertOk();

    $parent = Category::where('code', $parentCode)->firstOrFail();

    UnoPimAgentServer::tool(CategoryUpsertTool::class, [
        'categories' => [['code' => $childCode, 'name' => 'Child', 'parent_id' => $parent->id]],
    ])->assertOk();

    $child = Category::where('code', $childCode)->firstOrFail();

    UnoPimAgentServer::tool(CategoryUpsertTool::class, [
        'categories' => [['code' => $parentCode, 'parent_id' => $child->id]],
    ])->assertHasErrors()->assertDontSee('unexpected error');

    UnoPimAgentServer::tool(CategoryUpsertTool::class, [
        'categories' => [['code' => $parentCode, 'parent_id' => $parent->id]],
    ])->assertHasErrors()->assertDontSee('unexpected error');

    expect($parent->refresh()->parent_id)->toBeNull();

    $child->delete();
    $parent->refresh()->delete();
});

it('prefers a sku match over an id match when the identifier is numeric', function () {
    $other = Product::factory()->simple()->create();
    $numericSkuProduct = Product::factory()->simple()->create(['sku' => (string) $other->id]);

    $payload = $this->toolPayload(ProductGetTool::class, ['identifier' => (string) $other->id]);

    expect($payload['id'] ?? null)->toBe($numericSkuProduct->id);

    $byId = $this->toolPayload(ProductGetTool::class, ['identifier' => $other->id]);

    expect($byId['id'] ?? null)->toBe($numericSkuProduct->id);

    $numericSkuProduct->delete();

    expect($this->toolPayload(ProductGetTool::class, ['identifier' => $other->id])['id'] ?? null)->toBe($other->id);

    $other->delete();
});

it('accepts an integer identifier and requires one', function () {
    $product = Product::factory()->simple()->create();

    UnoPimAgentServer::tool(ProductGetTool::class, ['identifier' => $product->id])
        ->assertOk()->assertSee($product->sku);

    UnoPimAgentServer::tool(ProductGetTool::class, [])->assertHasErrors();

    $product->delete();
});
