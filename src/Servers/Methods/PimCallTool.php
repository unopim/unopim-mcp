<?php

namespace Webkul\MCP\Servers\Methods;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Mcp\Server\Methods\CallTool as BaseCallTool;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;
use Laravel\Mcp\Server\Transport\JsonRpcResponse;

class PimCallTool extends BaseCallTool
{
    /**
     * @return JsonRpcResponse|\Generator<JsonRpcResponse>
     */
    public function handle(JsonRpcRequest $request, ServerContext $context): \Generator|JsonRpcResponse
    {
        $toolName = $request->params['name'] ?? 'unknown';

        // 1. Rate Limiting
        $rateLimit = config('mcp.rate_limit', 60);
        $clientId = request()->ip() ?? 'cli';
        $rateLimitKey = "mcp-tool:{$toolName}:{$clientId}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, $rateLimit)) {
            return JsonRpcResponse::result($request->id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Rate limit exceeded. Please slow down your requests.',
                    ],
                ],
                'isError' => true,
            ]);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // 2. Authentication & Authorization
        $isAuthRequired = config('mcp.api_auth', true);
        $user = request()->user();

        // If auth is required by config, and it's not a CLI request, we MUST have an authenticated user
        if ($isAuthRequired && ! $user && ! app()->runningInConsole()) {
            return JsonRpcResponse::result($request->id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Unauthorized: This MCP server requires authentication.',
                    ],
                ],
                'isError' => true,
            ]);
        }

        // Authorization check: User must be present OR auth is required to proceed.
        // The isAuthorized method will handle CLI-specific bypass logic.
        if (($isAuthRequired || $user) && ! $this->isAuthorized($toolName)) {
            return JsonRpcResponse::result($request->id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Forbidden: Unauthorized access to executed [{$toolName}].",
                    ],
                ],
                'isError' => true,
            ]);
        }

        // 3. Execution (Parent Handle)
        $responseWrapper = parent::handle($request, $context);

        // 4. Audit Logging — only for successful mutations
        if (config('mcp.audit_logging', true) && ! $this->responseHasError($responseWrapper)) {
            $this->auditLog($toolName, $request->params['arguments'] ?? []);
        }

        return $responseWrapper;
    }

    /**
     * Check if the current user (or CLI agent) is authorized to execute the tool.
     */
    protected function isAuthorized(string $toolName): bool
    {
        // If running in console (STDIO mode) and NOT in a test environment,
        // we allow full access by default as the operator has machine access.
        if (app()->runningInConsole() && ! app()->environment('testing')) {
            return true;
        }

        $permissionMap = [
            // Catalog tools
            'get_catalog_schema' => 'catalog',
            'search_products' => 'catalog.products',
            'get_product' => 'catalog.products',
            'upsert_products' => 'catalog.products.create',
            'search_categories' => 'catalog.categories',
            'upsert_categories' => 'catalog.categories.create',
            'search_attributes' => 'catalog.attributes',
            'upsert_attributes' => 'catalog.attributes.create',

            // Settings tools
            'search_settings' => 'settings',
            'upsert_settings' => 'settings',

            // Dev tools — restricted to settings/admin
            'dev_tools' => 'settings',
            'run_skill' => 'settings',
        ];

        // If specific permission mapped, check it. Otherwise, assume they need generic catalog/settings access.
        $requiredPerm = $permissionMap[$toolName] ?? 'catalog';

        if (function_exists('bouncer')) {
            return bouncer()->hasPermission($requiredPerm) || bouncer()->hasPermission('settings');
        }

        return true;
    }

    /**
     * Check if a JSON-RPC response carries an isError flag.
     */
    protected function responseHasError(\Generator|JsonRpcResponse $response): bool
    {
        if ($response instanceof \Generator) {
            return false;
        }

        $result = $response->toArray()['result'] ?? [];

        return ($result['isError'] ?? false) === true;
    }

    /**
     * Log the tool execution if it modifies data.
     */
    protected function auditLog(string $toolName, array $arguments): void
    {
        $readOnlyTools = [
            'get_catalog_schema',
            'search_products',
            'get_product',
            'search_categories',
            'search_attributes',
            'search_settings',
        ];

        if (! in_array($toolName, $readOnlyTools)) {
            Log::info("MCP Audit Log: Executed [{$toolName}]", [
                'user_id' => request()->user()?->id ?? 'cli',
                'ip' => request()->ip() ?? 'local',
                'args' => $arguments,
            ]);
        }
    }
}
