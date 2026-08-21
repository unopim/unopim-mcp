<?php

namespace Webkul\MCP\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
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
    protected string $description = 'Execute read-only SQL queries against the database for data research and diagnostic purposes.';

    /**
     * The tool's name.
     */
    public string $name = 'run_database_query';

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'query' => ['required', 'string'],
        ]);

        $query = $validated['query'];

        // Strict read-only enforcement
        if (! preg_match('/^\s*select\s/i', $query)) {
            return Response::error('Only SELECT queries are allowed for security reasons.');
        }

        // Additional forbidden keywords
        $forbidden = ['insert', 'update', 'delete', 'drop', 'truncate', 'alter', 'grant', 'revoke'];
        foreach ($forbidden as $word) {
            if (preg_match("/\b{$word}\b/i", $query)) {
                return Response::error("Direct '{$word}' operations are strictly forbidden.");
            }
        }

        try {
            $results = DB::select($query);

            return Response::json([
                'count' => count($results),
                'results' => array_slice($results, 0, 100), // Limit results for JSON safety
                'clipped' => count($results) > 100,
            ]);
        } catch (\Throwable $e) {
            return Response::error('SQL Error: '.$e->getMessage());
        }
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The SQL SELECT query to execute.')->required(),
        ];
    }
}
