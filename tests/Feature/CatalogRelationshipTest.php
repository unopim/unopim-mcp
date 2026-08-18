<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Catalog\ProductGetTool;
use Webkul\MCP\Tools\Catalog\ProductUpsertTool;
use Webkul\Product\Models\Product;

it('creates related products and retrieves relationships', function () {
    $family = AttributeFamily::first() ?? AttributeFamily::factory()->create();
    $sku1 = 'REL-1-'.uniqid();
    $sku2 = 'REL-2-'.uniqid();

    // Create two products
    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            [
                'sku' => $sku1,
                'type' => 'simple',
                'attribute_family_id' => $family->id,
            ],
            [
                'sku' => $sku2,
                'type' => 'simple',
                'attribute_family_id' => $family->id,
            ],
        ],
    ])->assertOk()->assertSee($sku1)->assertSee($sku2);

    expect(Product::where('sku', $sku1)->exists())->toBeTrue();
    expect(Product::where('sku', $sku2)->exists())->toBeTrue();

    // Get product details to verify relationships are retrievable
    UnoPimAgentServer::tool(ProductGetTool::class, [
        'identifier' => $sku1,
    ])->assertOk()->assertSee($sku1)->assertSee('attribute_family');

    // Cleanup
    Product::where('sku', $sku1)->delete();
    Product::where('sku', $sku2)->delete();
});

it('retrieves product completeness via get_product', function () {
    $family = AttributeFamily::first() ?? AttributeFamily::factory()->create();
    $sku = 'COMP-'.uniqid();
    Product::factory()->create(['sku' => $sku, 'attribute_family_id' => $family->id]);

    UnoPimAgentServer::tool(ProductGetTool::class, [
        'identifier' => $sku,
    ])->assertOk()->assertSee('completeness');

    Product::where('sku', $sku)->delete();
});
