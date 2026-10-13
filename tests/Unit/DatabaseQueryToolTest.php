<?php

use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Webkul\MCP\Tools\Dev\DatabaseQueryTool;

function queryRejection(string $query, string $driver): ?string
{
    $method = new ReflectionMethod(DatabaseQueryTool::class, 'rejectionReason');

    return $method->invoke(app(DatabaseQueryTool::class), $query, $driver);
}

it('rejects comment and literal tricks that hide a forbidden call from the filter', function (string $driver, string $query) {
    expect(queryRejection($query, $driver))->not->toBeNull();
})->with([
    'mysql double dash without whitespace' => ['mysql', 'SELECT 1--1, SLEEP(3)'],
    'mariadb double dash without whitespace' => ['mariadb', 'SELECT 1--1, SLEEP(3)'],
    'mysql comment opening a quote' => ['mysql', "SELECT 1 -- '\n, SLEEP(3) -- '"],
    'mysql no backslash escapes mode' => ['mysql', "SELECT '\\', SLEEP(3), '\\'"],
    'mysql quoted function name' => ['mysql', 'SELECT `sleep`(3)'],
    'mysql comment between name and parenthesis' => ['mysql', 'SELECT SLEEP/**/(3)'],
    'mysql dollar identifier' => ['mysql', 'SELECT 1 AS $a$, SLEEP(3), 1 AS $a$'],
    'mysql desc analyze' => ['mysql', 'DESC ANALYZE SELECT SLEEP(2)'],
    'mysql describe analyze' => ['mysql', 'DESCRIBE ANALYZE SELECT SLEEP(2)'],
    'mysql show with where' => ['mysql', 'SHOW TABLES WHERE SLEEP(2)'],
    'pgsql hash is not a comment' => ['pgsql', "SELECT 1 # 1, pg_read_file('/etc/passwd')"],
    'pgsql standard conforming strings' => ['pgsql', "SELECT '\\', pg_sleep(3), '\\'"],
    'pgsql escape string' => ['pgsql', "SELECT E'\\'', pg_sleep(3) -- '"],
    'pgsql executable comment marker' => ['pgsql', "SELECT 1 /*! ' */, pg_sleep(3) -- ' */"],
    'pgsql quoted function name' => ['pgsql', 'SELECT "pg_sleep"(3)'],
    'pgsql unicode identifier' => ['pgsql', 'SELECT U&"\0070g_sleep"(3)'],
    'pgsql desc analyze' => ['pgsql', 'DESC ANALYZE SELECT pg_sleep(2)'],
    'unknown driver comment opening a quote' => ['sqlite', "SELECT 1 -- '\n, sleep(3) -- '"],
]);

it('still allows ordinary read queries on every driver', function (string $driver, string $query) {
    expect(queryRejection($query, $driver))->toBeNull();
})->with([
    ['mysql', "SELECT id, code FROM attributes WHERE code <> 'update' -- trailing note"],
    ['mysql', "SELECT id FROM attributes # note\nWHERE code LIKE 'a\\_b'"],
    ['mysql', 'SELECT 1 - -1 AS total'],
    ['mysql', 'SELECT /*+ MAX_EXECUTION_TIME(1000) */ id FROM attributes'],
    ['mysql', 'DESCRIBE attributes'],
    ['mysql', 'DESC attributes'],
    ['mysql', 'SHOW CREATE TABLE attributes'],
    ['mysql', "SHOW TABLES LIKE 'attr%'"],
    ['mariadb', 'WITH c AS (SELECT id FROM attributes) SELECT count(*) FROM c'],
    ['pgsql', "SELECT id FROM attributes WHERE code <> 'delete' -- note"],
    ['pgsql', 'SELECT $$sleep(1)$$ AS body, code FROM attributes'],
    ['pgsql', 'SELECT "code" FROM "attributes"'],
    ['pgsql', 'EXPLAIN SELECT id FROM attributes'],
    ['sqlite', "SELECT id FROM attributes WHERE code = 'drop'"],
]);

it('caps how long a query may run on the read only connection', function () {
    $method = new ReflectionMethod(DatabaseQueryTool::class, 'runReadOnly');
    $tool = app(DatabaseQueryTool::class);
    $connection = DB::connection();

    $timeout = match (true) {
        $connection instanceof MySqlConnection && $connection->isMaria() => (float) $method->invoke($tool, 'SELECT @@SESSION.max_statement_time AS t')[0]->t === 30.0,
        $connection instanceof MySqlConnection => (int) $method->invoke($tool, 'SELECT @@SESSION.max_execution_time AS t')[0]->t === 30000,
        $connection->getDriverName() === 'pgsql' => $method->invoke($tool, "SELECT current_setting('statement_timeout') AS t")[0]->t === '30s',
        default => true,
    };

    expect($timeout)->toBeTrue();
});
