<?php

namespace Webkul\MCP\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Webkul\Attribute\Models\Attribute;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Dev\AppInfoTool;
use Webkul\MCP\Tools\Dev\DatabaseQueryTool;
use Webkul\MCP\Tools\Dev\DatabaseSchemaTool;
use Webkul\MCP\Tools\Dev\LogReadTool;

it('returns app info through the mcp tool', function () {
    UnoPimAgentServer::tool(AppInfoTool::class)
        ->assertOk()
        ->assertSee('php_version')
        ->assertSee('laravel_version')
        ->assertSee('unopim_packages');
});

it('introspects database schema through the mcp tool', function () {
    // 1. List all tables
    $response = UnoPimAgentServer::tool(DatabaseSchemaTool::class);
    $response->assertOk();

    // 2. Introspect a specific core table (resolve prefix dynamically)
    $tableName = (new Attribute)->getTable();

    $response = UnoPimAgentServer::tool(DatabaseSchemaTool::class, [
        'table' => $tableName,
    ]);

    $response->assertOk()->assertSee('code')->assertSee('type');
});

it('executes read-only database queries through the mcp tool', function () {
    // 1. Valid select (resolve prefix dynamically)
    $attribute = new Attribute;
    $tableName = $attribute->getTable();
    $prefix = DB::getTablePrefix();
    $realTableName = $prefix.$tableName;

    $response = UnoPimAgentServer::tool(DatabaseQueryTool::class, [
        'query' => "SELECT count(*) as total FROM {$realTableName}",
    ]);

    $response->assertOk()->assertSee('total');

    // 2. Forbidden update
    UnoPimAgentServer::tool(DatabaseQueryTool::class, [
        'query' => "UPDATE {$realTableName} SET code = 'fail' WHERE id = 1",
    ])->assertSee('SELECT');
});

it('reads logs through the mcp tool', function () {
    // 1. Create a dummy log entry
    $logPath = storage_path('logs/laravel.log');
    $dummyMessage = 'MCP_TEST_LOG_ENTRY_'.uniqid();
    File::append($logPath, $dummyMessage."\n");

    // 2. Read via MCP
    UnoPimAgentServer::tool(LogReadTool::class, [
        'lines' => 5,
    ])->assertOk()->assertSee($dummyMessage)->assertSee('laravel.log');
});
