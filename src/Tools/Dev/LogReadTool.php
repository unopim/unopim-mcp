<?php

namespace Webkul\MCP\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\MCP\Tools\BaseMcpTool;

class LogReadTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Read the most recent entries from the UnoPim application logs for diagnostic purposes.';

    /**
     * The tool's name.
     */
    public string $name = 'read_logs';

    /**
     * Max lines allowed to read.
     */
    protected int $maxLines = 500;

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'lines' => ['nullable', 'integer', 'min:1', "max:{$this->maxLines}"],
            'type' => ['nullable', 'string', 'in:laravel,mcp'],
        ]);

        $lines = (int) ($validated['lines'] ?? 50);
        $type = $validated['type'] ?? 'laravel';

        $logPath = ($type === 'mcp')
            ? storage_path('logs/mcp.log')
            : storage_path('logs/laravel.log');

        if (! file_exists($logPath)) {
            return Response::error("Log file not found at [{$logPath}].");
        }

        // Efficient line reading via shell if possible, or PHP tail equivalent
        $content = $this->tail($logPath, $lines);

        return Response::json([
            'file' => basename($logPath),
            'lines' => $lines,
            'content' => $content,
        ]);
    }

    /**
     * Tail the file for N lines.
     */
    protected function tail(string $file, int $lines): string
    {
        if (app()->runningInConsole() || function_exists('shell_exec')) {
            $escapedFile = escapeshellarg($file);
            $output = shell_exec("tail -n {$lines} {$escapedFile}");

            if ($output !== null) {
                return $output;
            }
        }

        // Fallback or explicit PHP implementation
        $data = file($file);

        return implode('', array_slice($data, -$lines));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lines' => $schema->integer()
                ->description("Number of lines to read (max {$this->maxLines}).")
                ->default(50),
            'type' => $schema->string()
                ->description('Type of log: laravel (default) or mcp.')
                ->enum(['laravel', 'mcp']),
        ];
    }
}
