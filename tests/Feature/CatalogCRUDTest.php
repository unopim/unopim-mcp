<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
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
                'values' => ['common' => ['name' => 'Updated Name']],
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
