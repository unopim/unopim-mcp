<?php

namespace Webkul\MCP\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class DatabaseQueryTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Execute a single read-only SQL statement (SELECT, WITH, SHOW, EXPLAIN or DESCRIBE) against the database for data research and diagnostic purposes.';

    /**
     * The tool's name.
     */
    public string $name = 'run_database_query';

    /**
     * Statements a query may start with.
     *
     * @var list<string>
     */
    protected array $allowedStatements = ['select', 'with', 'show', 'explain', 'describe', 'desc'];

    /**
     * Seconds a single query may run before the server cancels it.
     */
    protected int $timeoutSeconds = 30;

    /**
     * Keywords that turn a SELECT, WITH or EXPLAIN into a write, a lock or a file export.
     *
     * @var list<string>
     */
    protected array $forbiddenKeywords = [
        'into', 'insert', 'update', 'delete', 'create', 'drop', 'alter', 'truncate', 'rename', 'grant',
        'revoke', 'call', 'execute', 'prepare', 'handler', 'lock', 'unlock', 'copy', 'vacuum', 'outfile', 'dumpfile',
    ];

    /**
     * Functions that stall the server or reach outside the database.
     *
     * @var list<string>
     */
    protected array $forbiddenFunctions = [
        'sleep', 'benchmark', 'get_lock', 'load_file', 'pg_sleep', 'pg_sleep_for', 'pg_sleep_until',
        'pg_read_file', 'pg_read_binary_file', 'pg_ls_dir', 'pg_stat_file', 'lo_import', 'lo_export',
        'dblink', 'dblink_exec', 'pg_terminate_backend', 'pg_cancel_backend', 'set_config',
        'pg_advisory_lock', 'pg_advisory_xact_lock', 'query_to_xml',
    ];

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:20000'],
        ]);

        $query = rtrim(trim($validated['query']), "; \t\n\r");

        if ($error = $this->rejectionReason($query)) {
            return Response::error($error);
        }

        try {
            $results = $this->runReadOnly($query);

            return Response::json([
                'count' => count($results),
                'results' => array_slice($results, 0, 100),
                'clipped' => count($results) > 100,
            ]);
        } catch (\Throwable $e) {
            return Response::error('SQL Error: '.$e->getMessage());
        }
    }

    /**
     * Explain why a query is refused, or return null when it is a single read statement.
     *
     * The query is lexed in the connection's dialect both with and without backslash escapes,
     * because `NO_BACKSLASH_ESCAPES`, `ANSI_QUOTES` and `standard_conforming_strings` change
     * where a literal ends, and every resulting skeleton must pass.
     */
    protected function rejectionReason(string $query, ?string $driver = null): ?string
    {
        $driver ??= DB::connection()->getDriverName();

        foreach ([true, false] as $backslashEscapes) {
            $skeleton = strtolower($this->stripLiteralsAndComments($query, $driver, $backslashEscapes));

            if ($reason = $this->skeletonRejectionReason($skeleton, $driver)) {
                return $reason;
            }
        }

        return null;
    }

    /**
     * Check a lexed, lowercased query for anything but a single read statement.
     */
    protected function skeletonRejectionReason(string $skeleton, string $driver): ?string
    {
        if (! preg_match('/^\s*\(*\s*('.implode('|', $this->allowedStatements).')\b/', $skeleton) || str_contains($skeleton, ';')) {
            return trans('mcp::app.dev.database-query.read-only');
        }

        $keywords = preg_replace('/^\s*\(*\s*show\s+create\b/', 'show', $skeleton) ?? $skeleton;

        if (preg_match('/\b('.implode('|', $this->forbiddenKeywords).')\b/', $keywords, $match)
            || preg_match('/\bfor\s+(share|no\s+key|key)\b/', $skeleton, $match)
            || preg_match('/\b('.implode('|', $this->forbiddenFunctions).')\s*\(/', $skeleton, $match)
            || ($driver === 'pgsql' && preg_match('/\b(u&)/', $skeleton, $match))) {
            return trans('mcp::app.dev.database-query.forbidden', ['keyword' => strtoupper($match[1])]);
        }

        return null;
    }

    /**
     * Run the query on a dedicated connection inside a read only transaction and roll it back.
     *
     * A separate connection guarantees no transaction is already open, which MySQL and
     * MariaDB require before `SET TRANSACTION READ ONLY`. Drivers without read only
     * transactions rely on the statement check alone.
     *
     * @return array<int, mixed>
     */
    protected function runReadOnly(string $query): array
    {
        $default = DB::connection();
        $driver = $default->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            return $default->select($query);
        }

        $name = 'mcp_read_only';

        $connection = DB::connectUsing($name, array_merge($default->getConfig(), ['name' => $name]), true);

        try {
            return $this->selectInReadOnlyTransaction($connection, $driver, $query);
        } finally {
            DB::purge($name);
        }
    }

    /**
     * @return array<int, mixed>
     */
    protected function selectInReadOnlyTransaction(Connection $connection, string $driver, string $query): array
    {
        if ($connection instanceof MySqlConnection) {
            $connection->unprepared($connection->isMaria()
                ? "SET SESSION max_statement_time = {$this->timeoutSeconds}"
                : 'SET SESSION max_execution_time = '.($this->timeoutSeconds * 1000));
            $connection->unprepared('SET TRANSACTION READ ONLY');
        }

        $connection->beginTransaction();

        try {
            if ($driver === 'pgsql') {
                $connection->unprepared('SET TRANSACTION READ ONLY');
                $connection->unprepared("SET LOCAL statement_timeout = '{$this->timeoutSeconds}s'");
            }

            return $connection->select($query);
        } finally {
            $connection->rollBack();
        }
    }

    /**
     * Blank out literals and comments in one left to right pass, keeping the statement shape.
     *
     * Quoted identifiers keep their name, since `` `sleep`(1) `` and `"pg_sleep"(1)` still call
     * the function. MySQL and MariaDB executable comments (`/*! ... *\/`) stay, because the
     * server runs their contents.
     */
    protected function stripLiteralsAndComments(string $query, string $driver, bool $backslashEscapes = true): string
    {
        [$comments, $identifiers, $literals] = match ($driver) {
            'mysql', 'mariadb' => [
                ['#[^\r\n]*', '--(?=[\x00-\x20]|\z)[^\r\n]*', '/\*(?![!]|[mM]!).*?(?:\*/|\z)'],
                [$this->quoted('`', false), $this->quoted('"', $backslashEscapes)],
                [$this->quoted("'", $backslashEscapes)],
            ],
            'pgsql' => [
                ['--[^\r\n]*', '/\*.*?(?:\*/|\z)'],
                [$this->quoted('"', false)],
                [
                    '(?<![\w$])[eE]'.$this->quoted("'", true),
                    $this->quoted("'", $backslashEscapes),
                    '(?<![\w$])\$(?<tag>(?:[A-Za-z_][A-Za-z0-9_]*)?)\$.*?\$\k<tag>\$',
                ],
            ],
            default => [
                ['--[^\r\n]*', '/\*.*?(?:\*/|\z)'],
                [$this->quoted('"', $backslashEscapes), $this->quoted('`', false), '\[[^\]]*\]'],
                [$this->quoted("'", $backslashEscapes)],
            ],
        };

        $pattern = '~(?<comment>'.implode('|', $comments).')|(?<identifier>'.implode('|', $identifiers).')|(?<literal>'.implode('|', $literals).')~s';

        return preg_replace_callback($pattern, fn (array $match): string => match (true) {
            $match['comment'] !== null => ' ',
            $match['identifier'] !== null => ' '.preg_replace('/\W/', '_', substr($match['identifier'], 1, -1)).' ',
            default => ' ? ',
        }, $query, flags: PREG_UNMATCHED_AS_NULL) ?? $query;
    }

    /**
     * A pattern for text between two quote characters that may be doubled, and optionally backslash escaped.
     */
    protected function quoted(string $quote, bool $backslashEscapes): string
    {
        return $backslashEscapes
            ? $quote.'(?:[^'.$quote.'\\\\]|\\\\.|'.$quote.$quote.')*'.$quote
            : $quote.'(?:[^'.$quote.']|'.$quote.$quote.')*'.$quote;
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('A single read-only SQL statement starting with SELECT, WITH, SHOW, EXPLAIN or DESCRIBE.')->required(),
        ];
    }
}
