<?php

use Webkul\Core\Models\Currency;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Settings\CurrencySearchTool;
use Webkul\MCP\Tools\Settings\CurrencyUpsertTool;

it('searches currencies through the mcp tool', function () {
    UnoPimAgentServer::tool(CurrencySearchTool::class)
        ->assertOk()
        ->assertSee('currencies');
});

it('creates and updates currencies via upsert', function () {
    $code = 'T'.rand(10, 99);

    // Create via upsert
    UnoPimAgentServer::tool(CurrencyUpsertTool::class, [
        'items' => [
            [
                'code' => $code,
                'name' => 'Test Currency',
                'status' => true,
            ],
        ],
    ])->assertOk()->assertSee($code)->assertSee('created');

    $currency = Currency::where('code', $code)->first();
    expect($currency)->not->toBeNull();

    // Update via upsert
    UnoPimAgentServer::tool(CurrencyUpsertTool::class, [
        'items' => [
            [
                'code' => $code,
                'name' => 'Updated Test Currency',
                'status' => false,
            ],
        ],
    ])->assertOk()->assertSee('updated');

    // Cleanup
    if ($currency) {
        $currency->delete();
    }
});
