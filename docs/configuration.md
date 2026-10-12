# Configuration

Reference for every configuration key in `config/mcp.php` and the env vars that drive them. The shipped defaults live in the package's `src/Config/mcp.php` and are merged at register time, so the published file only needs to list the keys you want to override.

## Publishing the config

`mcp:install` publishes `config/mcp.php` into the host application:

```bash
php artisan mcp:install
```

To re-publish (overwriting any local edits):

```bash
php artisan vendor:publish --provider="Webkul\MCP\Providers\MCPServiceProvider" --tag=mcp-config --force
```

## Full config reference

```php
return [
    // Bearer-token auth on all HTTP MCP endpoints (Passport `auth:api`).
    'api_auth' => env('MCP_API_AUTH', true),

    // Per-minute, per-tool, per-client request budget enforced by PimCallTool.
    'rate_limit' => env('MCP_RATE_LIMIT', 60),

    // Filesystem allowlist used by FileManager. Anything outside is rejected
    // (path traversal is normalized first).
    'allowed_paths' => [
        base_path(),
        sys_get_temp_dir(),
    ],

    // Log every destructive tool call (upsert, create, update, run_command).
    'audit_logging' => env('MCP_AUDIT_LOGGING', true),

    // Where SkillLoader scans for SKILL.md files.
    'skills_path' => env('MCP_SKILLS_PATH', base_path('.ai/skills')),

    // Skill registry caching.
    'enable_cache' => env('MCP_ENABLE_CACHE', true),
    'cache_key'    => 'mcp.skills',
    'cache_ttl'    => env('MCP_CACHE_TTL', 3600),

    // Reserved for future media tools — used to validate uploads.
    'media' => [
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'csv', 'xlsx'],
        'allowed_mimes'      => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            'application/pdf', 'text/csv', 'text/plain',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ],
];
```

## Key-by-key

### `api_auth` — `MCP_API_AUTH`
- **Default**: `true`
- Toggles the `auth:api` middleware on the HTTP route registered in `Routes/mcp-routes.php`. Set `false` only for local testing — production HTTP traffic must stay authenticated.
- The stdio transport (`mcp:start unopim-dev`) does not consult this flag; it is always invoked locally.

### `rate_limit` — `MCP_RATE_LIMIT`
- **Default**: `60` (requests per minute)
- Enforced by `PimCallTool` using Laravel's `RateLimiter` with the key `mcp-tool:{toolName}:{ip|cli}`. Counts are per tool, per caller — not per server-wide.
- Raise it for noisy AI agents; lower it to protect a slow database.

### `allowed_paths`
- **Default**: `[base_path(), sys_get_temp_dir()]`
- The `FileManager` (and every dev tool that touches disk) jails reads/writes inside this list. Paths are normalized first, so `..` traversal is rejected before the comparison runs.
- Add a path explicitly if you need the AI to write outside the project root (e.g. a separate storage volume). Anything you add becomes reachable, so think before you append.

### `audit_logging` — `MCP_AUDIT_LOGGING`
- **Default**: `true`
- When on, `PimCallTool` writes an `info`-level entry for every destructive call (`upsert_*`, `dev_tools` actions that mutate state). Includes user ID, IP, tool name, and arguments.
- Disable it only if your log channel can't keep up — never in production.

### `skills_path` — `MCP_SKILLS_PATH`
- **Default**: `base_path('.ai/skills')`
- Directory that `SkillLoader` recursively scans for `SKILL.md` files. Each parsed skill is registered as `execute_<snake_case_name>`.
- Must be an absolute path or one resolvable from `base_path()`.

### `enable_cache` — `MCP_ENABLE_CACHE`
- **Default**: `true`
- Caches the parsed skill registry under `cache_key` for `cache_ttl` seconds. Turn off during skill development so every server start re-scans the directory.

### `cache_key`
- **Default**: `'mcp.skills'`
- Cache entry name. Exposed so `php artisan cache:forget mcp.skills` works after editing a `SKILL.md` without flipping `enable_cache`.

### `cache_ttl` — `MCP_CACHE_TTL`
- **Default**: `3600` (seconds)
- TTL for the skill cache.

### `media`
- Reserved for upcoming media-management tools. Currently consumed by validation helpers; not enforced anywhere in the shipped catalog tools.
- `allowed_extensions` — file extensions accepted on upload.
- `allowed_mimes` — MIME types accepted on upload.

## Environment variables at a glance

| Env var | Config key | Default |
|---|---|---|
| `MCP_API_AUTH` | `api_auth` | `true` |
| `MCP_RATE_LIMIT` | `rate_limit` | `60` |
| `MCP_AUDIT_LOGGING` | `audit_logging` | `true` |
| `MCP_SKILLS_PATH` | `skills_path` | `base_path('.ai/skills')` |
| `MCP_ENABLE_CACHE` | `enable_cache` | `true` |
| `MCP_CACHE_TTL` | `cache_ttl` | `3600` |

## Production checklist

- `MCP_API_AUTH=true` and a real Passport client behind every HTTP caller.
- `MCP_AUDIT_LOGGING=true` plus a log channel that retains `info` entries long enough to investigate.
- `allowed_paths` trimmed to the directories the AI legitimately needs.
- `MCP_RATE_LIMIT` tuned to protect downstream services, not just the request handler.
- Skills shipped to production are reviewed — anything the loader picks up becomes a callable tool.

See [Troubleshooting → Configuration Sanity Checks](troubleshooting.md#configuration-sanity-checks) for symptom-to-config mappings.
