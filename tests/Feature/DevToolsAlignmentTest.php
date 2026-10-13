<?php

namespace Webkul\MCP\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
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

it('lists only the tables of the application database', function () {
    $response = app(DatabaseSchemaTool::class)->handle(new Request([]));

    $schemas = collect(DB::connection()->getSchemaBuilder()->getCurrentSchemaListing() ?? [])
        ->map(fn ($schema) => strtolower($schema));

    $tables = collect(json_decode((string) $response->content(), true)['tables'] ?? []);

    expect($tables)->not->toBeEmpty()
        ->and($tables->pluck('schema')->filter()->map(fn ($schema) => strtolower($schema))->unique()->diff($schemas)->all())->toBe([]);
});

it('allows read queries that mention write keywords inside literals, ctes and subqueries', function (string $query) {
    $table = DB::getTablePrefix().(new Attribute)->getTable();

    UnoPimAgentServer::tool(DatabaseQueryTool::class, ['query' => str_replace('{table}', $table, $query)])
        ->assertOk()
        ->assertSee('results');
})->with([
    "SELECT count(*) AS total FROM {table} WHERE code <> 'update'",
    'WITH c AS (SELECT id FROM {table}) SELECT count(*) AS total FROM c',
    'SELECT count(*) AS total FROM (SELECT id FROM {table}) AS sub',
    'select count(*) as total from {table};',
]);

it('rejects queries that write, chain statements or stall the database', function (string $query) {
    $table = DB::getTablePrefix().(new Attribute)->getTable();

    UnoPimAgentServer::tool(DatabaseQueryTool::class, ['query' => str_replace('{table}', $table, $query)])
        ->assertHasErrors();
})->with([
    'SELECT id INTO @x FROM {table} LIMIT 1',
    'SELECT * INTO mcp_copy FROM {table}',
    'SELECT sleep(5)',
    'SELECT pg_sleep(5)',
    'SELECT benchmark(100000000, md5(1))',
    'SELECT 1; DELETE FROM {table}',
    'WITH c AS (SELECT 1) DELETE FROM {table}',
    'DELETE FROM {table}',
    'SELECT id FROM {table} FOR UPDATE',
]);

it('runs the query inside a read only transaction that refuses writes', function () {
    if (! in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
        $this->markTestSkipped('Read only transactions are only enforced on MySQL, MariaDB and PostgreSQL.');
    }

    $table = DB::getTablePrefix().(new Attribute)->getTable();
    $method = new \ReflectionMethod(DatabaseQueryTool::class, 'runReadOnly');

    expect(fn () => $method->invoke(app(DatabaseQueryTool::class), "UPDATE {$table} SET code = code WHERE id = 0"))
        ->toThrow(QueryException::class);
});

it('no longer offers a log type that the package never writes', function () {
    UnoPimAgentServer::tool(LogReadTool::class, ['type' => 'mcp'])->assertHasErrors();
});
