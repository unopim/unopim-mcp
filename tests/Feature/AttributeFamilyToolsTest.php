<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Catalog\AttributeFamilySearchTool;
use Webkul\MCP\Tools\Catalog\AttributeFamilyUpsertTool;

it('searches families through the mcp tool', function () {
    UnoPimAgentServer::tool(AttributeFamilySearchTool::class)
        ->assertOk()
        ->assertSee('families');
});

it('creates and updates families via upsert', function () {
    $code = 'tf_'.rand(100, 999);

    // Create via upsert
    $response = UnoPimAgentServer::tool(AttributeFamilyUpsertTool::class, [
        'items' => [
            [
                'code' => $code,
                'name' => 'Test Family',
            ],
        ],
    ]);

    $response->assertOk()->assertSee($code)->assertSee('created');

    $family = AttributeFamily::where('code', $code)->first();
    expect($family)->not->toBeNull();

    // Update via upsert
    UnoPimAgentServer::tool(AttributeFamilyUpsertTool::class, [
        'items' => [
            [
                'code' => $code,
                'name' => 'Updated Test Family',
            ],
        ],
    ])->assertOk()->assertSee('updated');

    // Cleanup
    if ($family) {
        $family->delete();
    }
});
