<?php

use Webkul\Core\Models\Locale;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Settings\SettingSearchTool;
use Webkul\MCP\Tools\Settings\SettingUpsertTool;

it('searches channels through the settings tool', function () {
    UnoPimAgentServer::tool(SettingSearchTool::class, ['type' => 'channels'])
        ->assertOk()
        ->assertSee('channels');
});

it('searches locales through the settings tool', function () {
    UnoPimAgentServer::tool(SettingSearchTool::class, ['type' => 'locales'])
        ->assertOk()
        ->assertSee('locales');
});

it('validates channel upsert requires type and items', function () {
    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'channels',
        // Missing items
    ])->assertHasErrors();
});

it('creates and updates a locale via upsert', function () {
    $code = 'tl_'.rand(100, 999);

    // Create via upsert
    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'locales',
        'items' => [
            [
                'code' => $code,
                'status' => true,
            ],
        ],
    ])->assertOk()->assertSee($code);

    $locale = Locale::where('code', $code)->first();
    expect($locale)->not->toBeNull();

    // Update via upsert
    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'locales',
        'items' => [
            [
                'code' => $code,
                'status' => false,
            ],
        ],
    ])->assertOk()->assertSee('updated');

    // Cleanup
    if ($locale) {
        $locale->delete();
    }
});

it('rejects upsert with invalid settings type', function () {
    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'invalid',
        'items' => [['code' => 'test']],
    ])->assertHasErrors();
});
