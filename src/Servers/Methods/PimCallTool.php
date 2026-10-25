<?php

namespace Webkul\MCP\Servers\Methods;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Methods\CallTool as BaseCallTool;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Laravel\Mcp\Transport\JsonRpcResponse;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Product\Repositories\ProductRepository;

class PimCallTool extends BaseCallTool
{
    /**
     * @return JsonRpcResponse|\Generator<JsonRpcResponse>
     */
    public function handle(JsonRpcRequest $request, ServerContext $context): \Generator|JsonRpcResponse
    {
        $toolName = $request->params['name'] ?? 'unknown';
        $arguments = is_array($request->params['arguments'] ?? null) ? $request->params['arguments'] : [];

        // 1. Rate Limiting
        $rateLimit = config('mcp.rate_limit', 60);
        $rateLimitKey = "mcp-tool:{$toolName}:{$this->rateLimitClient()}";

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
        if (($isAuthRequired || $user) && ! $this->isAuthorized($toolName, $arguments)) {
            return JsonRpcResponse::result($request->id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => trans('mcp::app.call-tool.forbidden', ['tool' => $toolName]),
                    ],
                ],
                'isError' => true,
            ]);
        }

        // 3. Execution (Parent Handle)
        $responseWrapper = parent::handle($request, $context);

        // 4. Audit Logging — only for successful mutations
        if (config('mcp.audit_logging', true) && ! $this->responseHasError($responseWrapper)) {
            $this->auditLog($toolName, $arguments);
        }

        return $responseWrapper;
    }

    /**
     * Identify the caller for rate limiting: the authenticated user, else the client IP.
     */
    protected function rateLimitClient(): string
    {
        $user = request()->user() ?? auth()->guard('admin')->user();

        if ($user) {
            return 'user:'.$user->getAuthIdentifier();
        }

        $ip = request()->ip();

        return $ip ? 'ip:'.$ip : 'cli';
    }

    /**
     * Check if the current user (or CLI agent) is authorized to execute the tool.
     *
     * Tools without a mapped permission, including developer tools and dynamic skills,
     * require a full access role because they reach beyond the catalog. Upsert tools
     * require `.create` for records that do not exist yet and `.edit` for those that do.
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function isAuthorized(string $toolName, array $arguments = []): bool
    {
        if (app()->runningInConsole() && ! app()->environment('testing')) {
            return true;
        }

        if (! function_exists('bouncer')) {
            return true;
        }

        $permissions = $this->requiredPermissions($toolName, $arguments);

        if ($permissions === null) {
            return auth()->guard('admin')->user()?->role?->permission_type === 'all';
        }

        foreach ($permissions as $permission) {
            if (! bouncer()->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The permissions a tool call needs, or null when the tool has no ACL mapping.
     *
     * @param  array<string, mixed>  $arguments
     * @return list<string>|null
     */
    protected function requiredPermissions(string $toolName, array $arguments): ?array
    {
        $target = $this->upsertTargets($arguments)[$toolName] ?? null;

        if ($target !== null) {
            [$resource, $repository, $identifier, $argument] = $target;

            $items = is_array($arguments[$argument] ?? null) ? $arguments[$argument] : [];

            return $this->upsertPermissions($resource, $repository, $identifier, $items);
        }

        $permission = $this->permissionMap()[$toolName] ?? null;

        return $permission === null ? null : [$permission];
    }

    /**
     * The ACL resource, repository, identifier field and item list argument of each upsert tool.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, array{0: string, 1: class-string<Repository>, 2: string, 3: string}>
     */
    protected function upsertTargets(array $arguments): array
    {
        $targets = [
            'upsert_products' => ['catalog.products', ProductRepository::class, 'sku', 'products'],
            'upsert_categories' => ['catalog.categories', CategoryRepository::class, 'code', 'categories'],
            'upsert_attributes' => ['catalog.attributes', AttributeRepository::class, 'code', 'attributes'],
            'upsert_families' => ['catalog.families', AttributeFamilyRepository::class, 'code', 'items'],
            'upsert_attribute_groups' => ['catalog.attribute_groups', AttributeGroupRepository::class, 'code', 'items'],
            'upsert_currencies' => ['settings.currencies', CurrencyRepository::class, 'code', 'items'],
        ];

        $settingsTarget = match ($arguments['type'] ?? null) {
            'channels' => ['settings.channels', ChannelRepository::class, 'code', 'items'],
            'locales' => ['settings.locales', LocaleRepository::class, 'code', 'items'],
            default => null,
        };

        if ($settingsTarget !== null) {
            $targets['upsert_settings'] = $settingsTarget;
        }

        return $targets;
    }

    /**
     * Require `.create` when any item is new and `.edit` when any item already exists.
     *
     * A batch that cannot be resolved (too many items, or items without an identifier)
     * is treated as creating records, and an oversized batch as both creating and editing.
     *
     * @param  class-string<Repository>  $repository
     * @param  array<int|string, mixed>  $items
     * @return list<string>
     */
    protected function upsertPermissions(string $resource, string $repository, string $identifier, array $items): array
    {
        if (count($items) > 100) {
            return ["{$resource}.create", "{$resource}.edit"];
        }

        $identifiers = collect($items)
            ->map(fn ($item) => is_array($item) ? ($item[$identifier] ?? null) : null)
            ->map(fn ($value) => is_string($value) || is_int($value) ? (string) $value : null);

        $known = $identifiers->filter(fn ($value) => $value !== null && $value !== '')->unique()->values();

        $existing = $known->isEmpty()
            ? collect()
            : app($repository)->findWhereIn($identifier, $known->all(), [$identifier])
                ->pluck($identifier)
                ->map(fn ($value) => Str::lower((string) $value));

        $permissions = [];

        if ($identifiers->contains(fn ($value) => $value === null || $value === '' || ! $existing->contains(Str::lower($value)))) {
            $permissions[] = "{$resource}.create";
        }

        if ($existing->isNotEmpty()) {
            $permissions[] = "{$resource}.edit";
        }

        return $permissions ?: ["{$resource}.create"];
    }

    /**
     * The UnoPim ACL permission each read tool requires, and the fallback for upsert
     * tools whose arguments do not identify a target. The DAM tools use the ACL keys of the
     * optional unopim/dam package.
     *
     * @return array<string, string>
     */
    protected function permissionMap(): array
    {
        return [
            'get_catalog_schema' => 'catalog',
            'search_products' => 'catalog.products',
            'get_product' => 'catalog.products',
            'upsert_products' => 'catalog.products.create',
            'search_categories' => 'catalog.categories',
            'upsert_categories' => 'catalog.categories.create',
            'search_attributes' => 'catalog.attributes',
            'upsert_attributes' => 'catalog.attributes.create',
            'search_attribute_options' => 'catalog.attributes',
            'search_families' => 'catalog.families',
            'upsert_families' => 'catalog.families.create',
            'search_attribute_groups' => 'catalog.attribute_groups',
            'upsert_attribute_groups' => 'catalog.attribute_groups.create',
            'search_settings' => 'settings',
            'upsert_settings' => 'settings',
            'search_currencies' => 'settings.currencies',
            'upsert_currencies' => 'settings.currencies.create',
            'search_jobs' => 'data_transfer',
            'get_job_execution' => 'data_transfer.job_tracker',
            'search_assets' => 'dam.asset',
            'get_asset' => 'dam.asset.view',
            'browse_asset_directories' => 'dam.directory',
        ];
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
            'search_attribute_options',
            'search_families',
            'search_attribute_groups',
            'search_settings',
            'search_currencies',
            'search_jobs',
            'get_job_execution',
            'search_assets',
            'get_asset',
            'browse_asset_directories',
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
