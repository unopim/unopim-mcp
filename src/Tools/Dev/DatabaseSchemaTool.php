<?php

namespace Webkul\MCP\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class DatabaseSchemaTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Retrieve the schema information for database tables to understand the data structure.';

    /**
     * The tool's name.
     */
    public string $name = 'get_database_schema';

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'table' => ['nullable', 'string'],
        ]);

        $table = $validated['table'] ?? null;

        if ($table) {
            if (! Schema::hasTable($table)) {
                return Response::error("Table [{$table}] not found.");
            }

            $columns = Schema::getColumns($table);

            return Response::json([
                'table' => $table,
                'schema' => $columns,
            ]);
        }

        // List all tables
        $tables = Schema::getTables();

        return Response::json([
            'tables' => $tables,
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'table' => $schema->string()->description('Specific table name to introspect. If omitted, lists all tables.'),
        ];
    }
}
