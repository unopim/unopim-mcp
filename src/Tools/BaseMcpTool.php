<?php

namespace Webkul\MCP\Tools;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;

abstract class BaseMcpTool extends Tool
{
    /**
     * Execute the tool with standardized error handling and logging.
     */
    public function handle(Request $request): Response
    {
        try {
            return $this->execute($request);
        } catch (ValidationException $e) {
            // Let validation errors bubble up so the framework formats them properly.
            throw $e;
        } catch (Throwable $e) {
            $errorRef = Str::random(8);
            Log::error("MCP Tool Error [{$errorRef}]: ".$e->getMessage(), [
                'tool' => static::class,
                'args' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::error("An unexpected error occurred (Ref: {$errorRef}). Please check system logs for details.");
        }
    }

    /**
     * The actual implementation of the tool logic.
     */
    abstract protected function execute(Request $request): Response;
}
