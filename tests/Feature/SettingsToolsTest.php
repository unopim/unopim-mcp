<?php

use Webkul\Core\Models\Channel;
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

function mcpChannelPayload(string $code): array
{
    $channel = core()->getDefaultChannel();

    return [
        'code' => $code,
        'name' => 'MCP Channel',
        'root_category_id' => $channel->root_category_id,
        'locales' => [$channel->locales->first()->code],
        'currencies' => [$channel->currencies->first()->code],
    ];
}

it('persists the status submitted for a new locale', function () {
    $code = 'ms_'.rand(100, 999);

    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'locales',
        'items' => [['code' => $code, 'status' => true]],
    ])->assertOk();

    expect((bool) Locale::where('code', $code)->value('status'))->toBeTrue();
});

it('creates a channel with its root category, name, locales and currencies', function () {
    $code = 'mcp_ch_'.rand(100, 999);
    $payload = mcpChannelPayload($code);

    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'channels',
        'items' => [$payload],
    ])->assertOk()->assertSee('created');

    $channel = Channel::with(['locales', 'currencies'])->where('code', $code)->first();

    expect($channel->root_category_id)->toBe($payload['root_category_id'])
        ->and($channel->locales->pluck('code')->all())->toBe($payload['locales'])
        ->and($channel->currencies->pluck('code')->all())->toBe($payload['currencies'])
        ->and($channel->translate($payload['locales'][0])?->name)->toBe('MCP Channel');
});

it('rejects a new channel without a root category, locales or currencies', function () {
    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'channels',
        'items' => [['code' => 'mcp_ch_incomplete']],
    ])->assertHasErrors();

    expect(Channel::where('code', 'mcp_ch_incomplete')->exists())->toBeFalse();
});

it('rejects a channel that references unknown locales or currencies', function () {
    $payload = array_merge(mcpChannelPayload('mcp_ch_unknown'), ['locales' => ['zz_ZZ'], 'currencies' => ['ZZZ']]);

    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'channels',
        'items' => [$payload],
    ])->assertHasErrors();

    expect(Channel::where('code', 'mcp_ch_unknown')->exists())->toBeFalse();
});

it('updates only the submitted channel fields and keeps its locales and currencies', function () {
    $channel = core()->getDefaultChannel();
    $locales = $channel->locales->pluck('code')->sort()->values()->all();
    $currencies = $channel->currencies->pluck('code')->sort()->values()->all();
    $localeCode = $channel->locales->first()->code;

    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'channels',
        'items' => [['code' => $channel->code, 'labels' => [$localeCode => 'Renamed by MCP']]],
    ])->assertOk()->assertSee('updated');

    $channel = Channel::with(['locales', 'currencies'])->find($channel->id);

    expect($channel->locales->pluck('code')->sort()->values()->all())->toBe($locales)
        ->and($channel->currencies->pluck('code')->sort()->values()->all())->toBe($currencies)
        ->and($channel->translate($localeCode)?->name)->toBe('Renamed by MCP');
});

it('refuses to disable a locale that a channel uses', function () {
    $locale = core()->getDefaultChannel()->locales->first();

    UnoPimAgentServer::tool(SettingUpsertTool::class, [
        'type' => 'locales',
        'items' => [['code' => $locale->code, 'status' => false]],
    ])->assertHasErrors();

    expect((bool) Locale::find($locale->id)->status)->toBeTrue();
});

it('lists only the active locales when filtered by status', function () {
    $active = Locale::factory()->create(['code' => 'xa_AC', 'status' => 1]);
    $inactive = Locale::factory()->create(['code' => 'xi_IN', 'status' => 0]);

    $payload = $this->toolPayload(SettingSearchTool::class, [
        'type' => 'locales',
        'filters' => [['field' => 'status', 'operator' => '=', 'value' => 1]],
        'limit' => 100,
    ]);

    $codes = collect($payload['results'])->pluck('code');

    expect($codes)->toContain($active->code)
        ->not->toContain($inactive->code);
});

it('returns the locale and currency codes assigned to each channel', function () {
    $channel = core()->getDefaultChannel();

    $payload = $this->toolPayload(SettingSearchTool::class, [
        'type' => 'channels',
        'filters' => [['field' => 'code', 'operator' => '=', 'value' => $channel->code]],
    ]);

    $result = $payload['results'][0];

    expect($result['code'])->toBe($channel->code)
        ->and(collect($result['locales'])->sort()->values()->all())->toBe($channel->locales->pluck('code')->sort()->values()->all())
        ->and(collect($result['currencies'])->sort()->values()->all())->toBe($channel->currencies->pluck('code')->sort()->values()->all());
});
