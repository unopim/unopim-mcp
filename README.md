# UnoPim MCP Bridge

Empower AI assistants (GitHub Copilot, Claude, Cursor, Windsurf) to interact with your UnoPim catalog, settings, and codebase using the [Model Context Protocol (MCP)](https://modelcontextprotocol.io/).

> The bridge exposes **two transports in one package**:
> | Server | Transport | Best for |
> |---|---|---|
> | **HTTP Agent** | `POST /api/mcp/unopim` (SSE) | Remote AI assistants, PIM workflows |
> | **stdio Agent** | `php artisan mcp:start unopim-dev` | Coding agents (Copilot, Cursor, Claude Code) |

---

## Features

- **Full Catalog CRUD** — Search, get, and upsert products, categories, and attributes with cursor-paginated results and atomic batch operations (up to 50 items per call).
- **Catalog Schema Discovery** — `get_catalog_schema` returns filterable fields, supported operators, and pagination info for every entity type.
- **Settings Management** — Search and upsert channels and locales via unified `search_settings` / `upsert_settings` tools.
- **Developer Tools** — AI-driven file management, safe Artisan/Composer execution, plugin scaffolding, and test generation via the unified `dev_tools` action tool.
- **Dynamic AI Skills** — Drop a `SKILL.md` into `.ai/skills/` and it becomes an MCP tool — no code required.
- **Security** — Production-hardened with Path Traversal protection, Command Whitelisting, Rate Limiting, ACL enforcement, and Audit Logging.
- **Comprehensive Test Suite** — Unit and feature tests covering all tools, services, and security layers.

---
## 📖 Detailed Documentation

- **[Use Cases](docs/use-cases.md)**: Real-world scenarios for Catalog Managers, Developers, and Business Analysts.
- **[Development Workflow](docs/development-workflow.md)**: How to integrate MCP into your daily coding routine for UnoPim.
- **[Tool Reference](docs/tool-reference.md)**: Every tool with parameters, defaults, permissions, and query operators.
- **[Configuration](docs/configuration.md)**: Every `config/mcp.php` key, the env vars that drive it, and a production checklist.
- **[Troubleshooting](docs/troubleshooting.md)**: Fixes for the most common connection, auth, and tool-execution errors.
- **[UnoPim vs. Akeneo MCP](docs/comparison-and-benefits.md)**: Why the UnoPim bridge is the superior choice for developers.
- **[Extending the Bridge](docs/extending-mcp.md)**: Guide to creating custom "Skills" and implementing new Core Tools.

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | 8.2+    |
| UnoPim      | 1.0+    |
| Laravel     | 11.0+   |

---

## 🛠️ Quick Start & Setup

### 1. Installation
Install the package via composer into your UnoPim root:

```bash
composer require unopim/mcp
php artisan mcp:install
```

### 2. Basic Configuration
Ensure `APP_URL` is set in your `.env`. If you plan to use the HTTP transport (SSE) for remote access, you may need to generate an API token for your user.

### 3. Usage (The 10-Second Test)
To verify everything is working, run the inspector:
```bash
php artisan mcp:inspector unopim-dev
```
This will launch a web interface where you can test each tool manually.

---

## 🔌 Connecting to AI Editors / Agents

Register the MCP server in your editor's config file. Both HTTP and stdio transports are supported.

### VS Code / GitHub Copilot — `.vscode/mcp.json`

```jsonc
{
    "servers": {
        "unopim-dev": {
            "command": "php",
            "args": ["artisan", "mcp:start", "unopim-dev"],
            "cwd": "/path/to/your/unopim"
        },
        "unopim-http": {
            "url": "http://127.0.0.1:8000/api/mcp/unopim",
            "type": "http"
        }
    }
}
```

### Cursor — `Preferences > Models > MCP`

Add a new MCP server with the following settings:
- **Type**: `command`
- **Command**: `php artisan mcp:start unopim-dev`

### Claude Code

```bash
claude mcp add unopim-dev -- php artisan mcp:start unopim-dev
```

### claude.ai — Remote Custom Connector (OAuth)

No manual setup is required — the package registers everything at boot:
OAuth discovery + dynamic client registration endpoints, the Passport
`admin` guard binding, a `login` route alias to the admin login page, and
the guard-bridge middleware that maps the Passport token user onto
UnoPim's ACL (`bouncer()`).

1. Make sure Passport keys exist: `php artisan passport:keys` (skip if already generated).
2. In claude.ai go to **Settings > Connectors > Add custom connector**.
3. Enter your MCP endpoint: `https://your-unopim-host/mcp/unopim`.
4. Complete the OAuth flow — you are redirected to the UnoPim admin login, then back to claude.ai.

Tool calls respect the connected admin's ACL permissions.

### Windsurf — `~/.windsurf/mcp.json`

```jsonc
{
    "servers": {
        "unopim-dev": {
            "command": "php",
            "args": ["artisan", "mcp:start", "unopim-dev"],
            "cwd": "/path/to/your/unopim"
        }
    }
}
```

---

## Configuration (`config/mcp.php`)

`mcp:install` publishes the config file via the `mcp-config` tag. The published file mirrors `packages/Webkul/MCP/src/Config/mcp.php`:

```php
return [
    // Require a Bearer token (Passport `auth:api`) for HTTP MCP endpoints.
    'api_auth' => env('MCP_API_AUTH', true),

    // Max requests per minute per tool per caller (IP for HTTP, "cli" for stdio).
    'rate_limit' => env('MCP_RATE_LIMIT', 60),

    // FileManager / dev-tool jail. Anything outside is rejected.
    'allowed_paths' => [
        base_path(),
        sys_get_temp_dir(),
    ],

    // Log every destructive tool call (upserts, dev_tools mutations).
    'audit_logging' => env('MCP_AUDIT_LOGGING', true),

    // Where SkillLoader looks for SKILL.md files.
    'skills_path' => env('MCP_SKILLS_PATH', base_path('.ai/skills')),

    // Skill registry caching.
    'enable_cache' => env('MCP_ENABLE_CACHE', true),
    'cache_key'    => 'mcp.skills',
    'cache_ttl'    => env('MCP_CACHE_TTL', 3600),

    // Reserved for upcoming media tools.
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

Per-key behavior, env-var mapping, and a production checklist live in **[docs/configuration.md](docs/configuration.md)**.

---

## Available Tools

Source of truth: `packages/Webkul/MCP/src/Registry/ToolRegistry.php`. See [docs/tool-reference.md](docs/tool-reference.md) for parameters and permission mapping.

### Catalog (13 tools)

| Tool | Description |
|------|-------------|
| `get_catalog_schema` | Returns filterable fields, operators, and pagination info per entity. |
| `search_products` | Cursor-paginated product search with filters (max 100 per page). |
| `get_product` | Fetch full product details by ID or SKU with relationships and completeness. |
| `upsert_products` | Batch create/update products (max 50 per call, atomic transaction). |
| `search_categories` | Cursor-paginated category search with filters. |
| `upsert_categories` | Batch create/update categories (max 50 per call, atomic transaction). |
| `search_attributes` | Cursor-paginated attribute search with filters. |
| `upsert_attributes` | Batch create/update attributes (max 50 per call, atomic transaction). |
| `search_attribute_options` | Cursor-paginated search across attribute options. |
| `search_families` | Search attribute families. |
| `upsert_families` | Batch create/update attribute families. |
| `search_attribute_groups` | Search attribute groups. |
| `upsert_attribute_groups` | Batch create/update attribute groups. |

### Settings (4 tools)

| Tool | Description |
|------|-------------|
| `search_settings` | Search channels or locales (pass `type`: `channels` or `locales`). |
| `upsert_settings` | Create/update channels or locales (max 50 per call). |
| `search_currencies` | Search currencies with filters. |
| `upsert_currencies` | Batch create/update currencies (max 50 per call). |

### Data Transfer (2 tools)

| Tool | Description |
|------|-------------|
| `search_jobs` | Search import/export job instances by `code`, `type`, `entity_type`, `action`. |
| `get_job_execution` | Fetch a single job execution (JobTrack) by ID with status, counts, and errors. |

### Developer Tools (6 core + dynamic)

| Tool | Description |
|------|-------------|
| `dev_tools` | Unified action tool with 6 actions: `create_file`, `read_file`, `update_file`, `run_command`, `generate_plugin`, `generate_test`. |
| `run_skill` | Execute a predefined skill from `.ai/skills/` by name. |
| `get_app_info` | Inspect the host app — Laravel/PHP versions, environment, installed packages. |
| `get_database_schema` | Introspect tables, columns, and relationships. Pass a `table` to scope. |
| `run_database_query` | Execute a read-only SQL query against the configured connection. |
| `read_logs` | Tail entries from `storage/logs/laravel.log` (and named log channels). |
| Dynamic Skills | Each `SKILL.md` under `mcp.skills_path` is auto-registered as `execute_<skill_name>`. |

### Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan mcp:install` | Run Passport scaffolding (if installed), publish `config/mcp.php`, and clear caches. |
| `php artisan mcp:make plugin <Name> [--type=connector\|core-extension\|generic]` | Scaffold a complete UnoPim plugin. |
| `php artisan mcp:make test <Package> <Class>` | Generate a Pest test skeleton for a class. |
| `php artisan mcp:dev` | Alias for `mcp:start unopim-dev` — starts the stdio server for local coding agents. |
| `php artisan mcp:start <handle>` | Start an MCP server over stdio (provided by `laravel/mcp`). |
| `php artisan mcp:inspector <server>` | Launch the MCP Inspector against a stdio handle (e.g. `unopim-dev`) or HTTP path (e.g. `mcp/unopim`). |

### MCP Resources & Prompts

| Name | Type | Description |
|------|------|-------------|
| `catalog-schema` | Resource | High-level catalog summary (product, category, attribute counts). |
| `analyze-catalog` | Prompt | Guided catalog analysis (completeness, consistency, optimization). |

---

## Query Operators

All search tools support the following filter operators:

| Operator | Description | Example |
|----------|-------------|---------|
| `=` | Equals | `{"field": "status", "operator": "=", "value": "active"}` |
| `!=` | Not equals | `{"field": "type", "operator": "!=", "value": "bundle"}` |
| `IN` | In list | `{"field": "type", "operator": "IN", "value": ["simple", "configurable"]}` |
| `NOT IN` | Not in list | `{"field": "id", "operator": "NOT IN", "value": [1, 2]}` |
| `CONTAINS` | Like %value% | `{"field": "name", "operator": "CONTAINS", "value": "shirt"}` |
| `STARTS WITH` | Like value% | `{"field": "sku", "operator": "STARTS WITH", "value": "PRD"}` |
| `ENDS WITH` | Like %value | `{"field": "sku", "operator": "ENDS WITH", "value": "001"}` |
| `>` | Greater than | `{"field": "price", "operator": ">", "value": 100}` |
| `<` | Less than | `{"field": "stock", "operator": "<", "value": 5}` |

---

## Dynamic Skills

Create a `SKILL.md` file with YAML frontmatter in `.ai/skills/<skill-name>/SKILL.md`:

```markdown
---
name: My Custom Skill
description: Automates a specific workflow
license: MIT
parameters:
  query:
    type: string
    required: true
metadata:
  author: your-name
---

# Instructions

Describe what this skill does and how to use it.
```

The skill is automatically discovered, cached, and registered as an MCP tool named `execute_my_custom_skill`.

---

## Security Policy

The UnoPim MCP Bridge implements multi-layer security:

1. **Request Authentication**: HTTP SSE endpoints default to `auth:api` (OAuth2 via Laravel Passport).
2. **Rate Limiting**: Configurable per-minute limit per tool per client IP (default: 60 req/min).
3. **Path Traversal Guard**: All file operations are jailed within configured `allowed_paths` with `.`/`..` normalization.
4. **Command Whitelisting**: Only `php artisan` and `composer` base commands are allowed. Shell operators (`;`, `&`, `|`, `` ` ``, `$()`, `<`, `>`) are blocked.
5. **ACL Mapping**: Every MCP tool maps to internal UnoPim permissions via `bouncer()`. CLI bypasses ACL for local development.
6. **Audit Logging**: All destructive operations (upsert, create, update) are logged with user ID, IP, tool name, and arguments.
7. **MIME Validation Config**: Media upload restrictions are defined in `config/mcp.php` for future media tool implementations.

---

## Architecture

```
packages/Webkul/MCP/
├── src/
│   ├── Config/mcp.php                    # Configuration (auth, rate limits, paths, media)
│   ├── Console/Commands/                  # Artisan commands (install, make, dev, inspector)
│   ├── Contracts/                         # FileManagerInterface, SkillExecutorInterface
│   ├── DevTools/                          # FileManager, CommandRunner, PluginGenerator, TestGenerator
│   ├── Prompts/Catalog/                   # CatalogAnalysisPrompt
│   ├── Providers/MCPServiceProvider.php   # Service registration, route loading
│   ├── Registry/ToolRegistry.php          # Static registry of 25 core tools
│   ├── Resources/Catalog/                 # CatalogSchemaResource
│   ├── Routes/mcp-routes.php             # HTTP endpoint registration (POST /api/mcp/unopim)
│   ├── Servers/
│   │   ├── UnoPimAgentServer.php          # Main MCP server (tools, resources, prompts, skills)
│   │   └── Methods/PimCallTool.php        # Auth, rate limiting, ACL, audit proxy
│   ├── Services/                          # SkillExecutor, SkillLoader, SkillParser, UnoPimQueryBuilder
│   └── Tools/
│       ├── BaseMcpTool.php                # Abstract base with error handling
│       ├── Catalog/                       # 13 catalog tools (schema, products, categories, attributes, families, groups, options)
│       ├── DataTransfer/                  # JobSearchTool, JobExecutionTool
│       ├── Dev/                           # DevToolsTool, RunSkillTool, DynamicSkillTool, AppInfoTool, DatabaseSchemaTool, DatabaseQueryTool, LogReadTool
│       └── Settings/                      # SettingSearchTool, SettingUpsertTool, CurrencySearchTool, CurrencyUpsertTool
├── tests/
│   ├── Pest.php                           # Pest configuration
│   ├── MCPTestCase.php                    # Base test class
│   ├── Unit/                              # 10 unit test files
│   └── Feature/                           # 11 feature test files
├── composer.json
├── README.md
├── CHANGELOG.md
└── LICENSE.md
```

---

## Testing

Run the full test suite:

```bash
cd packages/Webkul/MCP
../../vendor/bin/pest
```

Or from project root:

```bash
./vendor/bin/pest --filter=MCP
```

The test suite covers:
- **Unit tests**: FileManager, CommandRunner, PluginGenerator, TestGenerator, SkillParser, SkillLoader, SkillExecutor, UnoPimQueryBuilder, PimCallTool, ToolRegistry
- **Feature tests**: Catalog CRUD, Settings management, Bulk operations, Security, DevTools integration, Dynamic Skills, Server registration, Artisan commands

---

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md) for details.
