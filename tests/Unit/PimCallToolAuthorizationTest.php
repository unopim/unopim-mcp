<?php

use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Category\Models\Category;
use Webkul\MCP\Servers\Methods\PimCallTool;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function actingAsAdminWith(string $permissionType, array $permissions = []): void
{
    $role = Role::factory()->create(['permission_type' => $permissionType, 'permissions' => $permissions]);

    auth()->guard('admin')->setUser(Admin::factory()->create(['role_id' => $role->id]));
}

function canCallTool(string $toolName): bool
{
    $method = new ReflectionMethod(PimCallTool::class, 'isAuthorized');

    return $method->invoke(app(PimCallTool::class), $toolName);
}

it('keeps developer tools away from an admin who only has catalog access', function (string $toolName) {
    actingAsAdminWith('custom', ['catalog', 'catalog.products']);

    expect(canCallTool($toolName))->toBeFalse();
})->with([
    'dev_tools',
    'run_skill',
    'get_app_info',
    'get_database_schema',
    'run_database_query',
    'read_logs',
]);

it('does not let settings access stand in for catalog permissions', function () {
    actingAsAdminWith('custom', ['settings', 'settings.locales']);

    expect(canCallTool('upsert_products'))->toBeFalse()
        ->and(canCallTool('search_products'))->toBeFalse();
});

it('checks the permission that matches each catalog tool', function () {
    actingAsAdminWith('custom', ['catalog', 'catalog.families']);

    expect(canCallTool('search_families'))->toBeTrue()
        ->and(canCallTool('upsert_families'))->toBeFalse()
        ->and(canCallTool('search_attribute_groups'))->toBeFalse();
});

it('maps currency and data transfer tools to their own permissions', function () {
    actingAsAdminWith('custom', ['data_transfer', 'data_transfer.job_tracker']);

    expect(canCallTool('search_jobs'))->toBeTrue()
        ->and(canCallTool('get_job_execution'))->toBeTrue()
        ->and(canCallTool('search_currencies'))->toBeFalse();
});

it('maps the dam tools to the dam package permissions', function () {
    actingAsAdminWith('custom', ['dam', 'dam.asset', 'dam.asset.view', 'dam.directory']);

    expect(canCallTool('search_assets'))->toBeTrue()
        ->and(canCallTool('get_asset'))->toBeTrue()
        ->and(canCallTool('browse_asset_directories'))->toBeTrue();
});

it('checks the permission that matches each dam tool', function () {
    actingAsAdminWith('custom', ['dam', 'dam.asset']);

    expect(canCallTool('search_assets'))->toBeTrue()
        ->and(canCallTool('get_asset'))->toBeFalse()
        ->and(canCallTool('browse_asset_directories'))->toBeFalse();
});

it('keeps the dam tools away from an admin without dam access', function (string $toolName) {
    actingAsAdminWith('custom', ['catalog', 'catalog.products', 'settings']);

    expect(canCallTool($toolName))->toBeFalse();
})->with([
    'search_assets',
    'get_asset',
    'browse_asset_directories',
]);

it('requires full access for a tool it has no permission mapping for', function () {
    actingAsAdminWith('custom', ['catalog', 'catalog.products', 'settings']);

    expect(canCallTool('some_dynamic_skill'))->toBeFalse();
});

it('lets a full access admin call developer and unmapped tools', function () {
    actingAsAdminWith('all');

    expect(canCallTool('run_database_query'))->toBeTrue()
        ->and(canCallTool('some_dynamic_skill'))->toBeTrue();
});

function canCallToolWith(string $toolName, array $arguments): bool
{
    $method = new ReflectionMethod(PimCallTool::class, 'isAuthorized');

    return $method->invoke(app(PimCallTool::class), $toolName, $arguments);
}

it('requires the create permission to upsert a record that does not exist yet', function () {
    actingAsAdminWith('custom', ['catalog', 'catalog.products', 'catalog.products.edit']);

    expect(canCallToolWith('upsert_products', ['products' => [['sku' => 'mcp-acl-new-'.uniqid()]]]))->toBeFalse();
});

it('reads each upsert tool\'s own argument key when resolving existing records', function (string $toolName, string $key, string $resource, Closure $existing) {
    $identifier = $toolName === 'upsert_products' ? 'sku' : 'code';
    $existingId = $existing();
    $newId = 'mcpaclnew'.strtolower(uniqid());

    actingAsAdminWith('custom', [explode('.', $resource)[0], $resource, "{$resource}.create"]);

    expect(canCallToolWith($toolName, [$key => [[$identifier => $existingId]]]))->toBeFalse()
        ->and(canCallToolWith($toolName, [$key => [[$identifier => $newId]]]))->toBeTrue()
        ->and(canCallToolWith($toolName, [$key => [[$identifier => $existingId], [$identifier => $newId]]]))->toBeFalse();

    actingAsAdminWith('custom', [explode('.', $resource)[0], $resource, "{$resource}.edit"]);

    expect(canCallToolWith($toolName, [$key => [[$identifier => $existingId]]]))->toBeTrue()
        ->and(canCallToolWith($toolName, [$key => [[$identifier => $existingId], [$identifier => $newId]]]))->toBeFalse();

    actingAsAdminWith('custom', [explode('.', $resource)[0], $resource, "{$resource}.create", "{$resource}.edit"]);

    expect(canCallToolWith($toolName, [$key => [[$identifier => $existingId], [$identifier => $newId]]]))->toBeTrue();
})->with([
    'products' => ['upsert_products', 'products', 'catalog.products', fn () => Product::factory()->create()->sku],
    'categories' => ['upsert_categories', 'categories', 'catalog.categories', fn () => Category::query()->value('code')],
    'attributes' => ['upsert_attributes', 'attributes', 'catalog.attributes', fn () => Attribute::query()->value('code')],
    'families' => ['upsert_families', 'items', 'catalog.families', fn () => AttributeFamily::query()->value('code')],
    'attribute groups' => ['upsert_attribute_groups', 'items', 'catalog.attribute_groups', fn () => AttributeGroup::query()->value('code')],
    'currencies' => ['upsert_currencies', 'items', 'settings.currencies', fn () => core()->getAllCurrencies()->first()->code],
]);

it('requires the edit permission to upsert a record that already exists', function () {
    $currency = core()->getAllCurrencies()->first();

    actingAsAdminWith('custom', ['settings', 'settings.currencies', 'settings.currencies.create']);

    expect(canCallToolWith('upsert_currencies', ['items' => [['code' => $currency->code]]]))->toBeFalse();

    actingAsAdminWith('custom', ['settings', 'settings.currencies', 'settings.currencies.edit']);

    expect(canCallToolWith('upsert_currencies', ['items' => [['code' => $currency->code]]]))->toBeTrue();
});

it('requires both permissions when a batch mixes new and existing records', function () {
    $currency = core()->getAllCurrencies()->first();
    $items = ['items' => [['code' => $currency->code], ['code' => 'XQZ']]];

    actingAsAdminWith('custom', ['settings', 'settings.currencies', 'settings.currencies.edit']);
    expect(canCallToolWith('upsert_currencies', $items))->toBeFalse();

    actingAsAdminWith('custom', ['settings', 'settings.currencies', 'settings.currencies.create', 'settings.currencies.edit']);
    expect(canCallToolWith('upsert_currencies', $items))->toBeTrue();
});

it('keeps a view only settings role from upserting locales or channels', function () {
    actingAsAdminWith('custom', ['settings', 'settings.locales', 'settings.channels']);

    expect(canCallToolWith('upsert_settings', ['type' => 'locales', 'items' => [['code' => 'zz_'.uniqid()]]]))->toBeFalse()
        ->and(canCallToolWith('upsert_settings', ['type' => 'channels', 'items' => [['code' => 'default']]]))->toBeFalse();
});

it('maps settings upserts to the channel and locale permissions', function () {
    actingAsAdminWith('custom', ['settings', 'settings.locales', 'settings.locales.create', 'settings.channels', 'settings.channels.edit']);

    expect(canCallToolWith('upsert_settings', ['type' => 'locales', 'items' => [['code' => 'zz_'.uniqid()]]]))->toBeTrue()
        ->and(canCallToolWith('upsert_settings', ['type' => 'channels', 'items' => [['code' => 'default']]]))->toBeTrue()
        ->and(canCallToolWith('upsert_settings', ['type' => 'channels', 'items' => [['code' => 'mcp_ch_'.uniqid()]]]))->toBeFalse();
});

it('words the denial message grammatically', function () {
    config(['mcp.api_auth' => true]);

    actingAsAdminWith('custom', ['catalog']);

    $request = new JsonRpcRequest('1', 'tools/call', ['name' => 'upsert_currencies', 'arguments' => []]);
    $response = app(PimCallTool::class)->handle($request, Mockery::mock(ServerContext::class))->toArray();

    expect($response['result']['content'][0]['text'])
        ->toBe(trans('mcp::app.call-tool.forbidden', ['tool' => 'upsert_currencies']))
        ->not->toContain('executed');
});
