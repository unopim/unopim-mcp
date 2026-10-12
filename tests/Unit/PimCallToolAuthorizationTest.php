<?php

use Webkul\MCP\Servers\Methods\PimCallTool;
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

it('requires full access for a tool it has no permission mapping for', function () {
    actingAsAdminWith('custom', ['catalog', 'catalog.products', 'settings']);

    expect(canCallTool('some_dynamic_skill'))->toBeFalse();
});

it('lets a full access admin call developer and unmapped tools', function () {
    actingAsAdminWith('all');

    expect(canCallTool('run_database_query'))->toBeTrue()
        ->and(canCallTool('some_dynamic_skill'))->toBeTrue();
});
