# Troubleshooting

Quick answers for the most common issues when running the UnoPim MCP Bridge.

## Connection & Transport

### AI client cannot connect to the stdio server
- Confirm the server starts standalone: `php artisan mcp:start unopim-dev`. If it fails here, the AI client will never connect.
- The `cwd` in your editor's MCP config must point to the UnoPim project root (where `artisan` lives).
- If the command hangs silently, make sure you haven't enabled extra output in `bootstrap/app.php` or a custom service provider — stdio transport requires that **only** JSON-RPC frames are written to `stdout`.

### HTTP `POST /mcp/unopim` returns `401 Unauthorized`
- Either disable auth for local testing with `MCP_API_AUTH=false`, or include a valid Bearer token. The HTTP route applies `auth:api` (Passport) when `config('mcp.api_auth')` is `true`.
- In the browser-based inspector, sessions behind CSRF guards won't work — the endpoint lives under the `api` middleware group.

### HTTP requests fail with `419 PAGE EXPIRED` or a CSRF error
The MCP HTTP route is registered under the `api` middleware group and should **not** be caught by web CSRF. If you see this, a custom middleware or route override is re-applying `VerifyCsrfToken`. Re-check `MCPServiceProvider::boot()` and your app's global middleware.

---

## Tool Execution

### `Forbidden: Unauthorized access to executed [tool]`
Coming from `PimCallTool::isAuthorized()`. Either:
- Grant the caller the mapped permission (see [Tool Reference → Permission Mapping](tool-reference.md#permission-mapping)).
- Developer tools and dynamic skills need a role with full access, and over HTTP also `MCP_REMOTE_DEVELOPER_TOOLS=true`.
- Use the stdio transport for local development — it bypasses ACL unless `APP_ENV=testing`.

### `Rate limit exceeded. Please slow down your requests.`
Tripped by `RateLimiter` in `PimCallTool`. Raise `MCP_RATE_LIMIT` (default `60` per minute per tool per IP) or back off the caller. The key format is `mcp-tool:{toolName}:{ip|cli}`.

### `An unexpected error occurred (Ref: XXXX)`
`BaseMcpTool::handle()` logs the full exception under that reference and returns a sanitized error. Grep `storage/logs/laravel.log` for the ref:

```bash
grep "Ref: XXXX" storage/logs/laravel.log -A 40
```

### Upsert reports success but changes weren't persisted
If a tool wraps writes in `DB::beginTransaction()` and returns early without `DB::rollBack()` or `DB::commit()`, the transaction stays open for the rest of the request and is discarded. If you wrote a custom upsert tool and see this symptom, audit every `return Response::error(...)` inside the `try` block and make sure it rolls back first.

---

## Skills (Dynamic Tools)

### A new `SKILL.md` doesn't show up as a tool
- Skills are cached for `config('mcp.cache_ttl')` seconds (default `3600`). Clear the cache:
  ```bash
  php artisan cache:forget mcp.skills
  ```
  Or temporarily set `MCP_ENABLE_CACHE=false`.
- The parser skips files whose frontmatter lacks a `name`. Check the YAML block.
- `SkillLoader` logs conflicts when two skills normalize to the same tool key:
  ```
  MCP Skill Conflict: Tool name [foo_bar] already exists...
  ```

### Skill parameters aren't advertised correctly
`DynamicSkillTool::schema()` only understands `type: string|integer|number|boolean`. Any other type falls back to `string`. Mark required params by listing them under `parameters.required` **or** using `required: true` on the individual parameter — whichever `SkillParser` writes into the parsed array.

---

## Dev Tools

### `run_command` refuses a command
`CommandRunner` whitelists base commands (`php artisan`, `composer`) and blocks shell operators (`;`, `&`, `|`, backticks, `$()`, `<`, `>`). Run complex pipelines manually, or extend `CommandRunner` and re-bind it in a custom service provider.

### `create_file` / `read_file` refuses a path
`FileManager` jails every operation inside `config('mcp.allowed_paths')`. To allow writes outside `base_path()` (e.g. a separate storage volume), append the path to `allowed_paths` — but remember that anything there is now reachable by the AI.

---

## Configuration Sanity Checks

| Symptom | Likely config |
|---|---|
| Anyone can call destructive tools from the web | `MCP_API_AUTH=false` in production. Flip it back on. |
| Audit log is empty after write operations | `MCP_AUDIT_LOGGING=false`, or the Laravel log channel doesn't include `info` level. |
| Skills not loading from a custom directory | `MCP_SKILLS_PATH` must be an absolute path or resolvable from `base_path()`. |
| Cache never invalidates during development | Set `MCP_ENABLE_CACHE=false` locally. |

---

## Getting a Clean Baseline

When in doubt, reset to a known-good state:

```bash
php artisan config:clear
php artisan cache:clear
php artisan cache:forget mcp.skills
php artisan mcp:install      # re-runs OAuth / publish / clear steps
php artisan mcp:inspector unopim-dev
```

If the inspector works but your AI client doesn't, the problem is on the client side — check its MCP server config for typos in `command`, `args`, or `cwd`.
