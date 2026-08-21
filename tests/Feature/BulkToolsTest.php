<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Catalog\ProductUpsertTool;
use Webkul\Product\Models\Product;

it('creates products in bulk via upsert', function () {
    $family = AttributeFamily::first() ?? AttributeFamily::factory()->create();
    $sku1 = 'BULK-1-'.uniqid();
    $sku2 = 'BULK-2-'.uniqid();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            [
                'sku' => $sku1,
                'type' => 'simple',
                'attribute_family_id' => $family->id,
                'values' => ['common' => ['name' => 'Bulk Product 1']],
            ],
            [
                'sku' => $sku2,
                'type' => 'simple',
                'attribute_family_id' => $family->id,
                'values' => ['common' => ['name' => 'Bulk Product 2']],
            ],
        ],
    ])->assertOk()->assertSee($sku1)->assertSee($sku2);

    expect(Product::where('sku', $sku1)->exists())->toBeTrue();
    expect(Product::where('sku', $sku2)->exists())->toBeTrue();

    // Cleanup
    Product::whereIn('sku', [$sku1, $sku2])->delete();
});

it('updates products in bulk via upsert', function () {
    $family = AttributeFamily::first() ?? AttributeFamily::factory()->create();
    $sku1 = 'BULK-U1-'.uniqid();
    $sku2 = 'BULK-U2-'.uniqid();

    // Setup: create products first
    Product::factory()->create(['sku' => $sku1, 'attribute_family_id' => $family->id]);
    Product::factory()->create(['sku' => $sku2, 'attribute_family_id' => $family->id]);

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            [
                'sku' => $sku1,
                'values' => ['common' => ['name' => 'Updated Bulk 1']],
            ],
            [
                'sku' => $sku2,
                'values' => ['common' => ['name' => 'Updated Bulk 2']],
            ],
        ],
    ])->assertOk()->assertSee('updated');

    // Cleanup
    Product::whereIn('sku', [$sku1, $sku2])->delete();
});

it('rejects batch exceeding 50 items', function () {
    $products = [];
    for ($i = 0; $i < 51; $i++) {
        $products[] = [
            'sku' => 'OVER-'.$i.'-'.uniqid(),
            'type' => 'simple',
            'attribute_family_id' => 1,
        ];
    }

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => $products,
    ])->assertHasErrors();
});
