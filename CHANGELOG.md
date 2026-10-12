# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - Unreleased

### Security
- Every catalog, settings and data transfer tool now checks its own UnoPim ACL permission. Tools without a mapping, including developer tools and dynamic skills, require a full access role. Previously they fell back to the `catalog` permission, and any `settings` permission granted every tool.
- The remote HTTP endpoint no longer serves developer tools (`dev_tools`, `run_skill`, `get_app_info`, `get_database_schema`, `run_database_query`, `read_logs`) or dynamic skills unless `MCP_REMOTE_DEVELOPER_TOOLS=true`. Stdio (`mcp:dev`) is unchanged.

### Fixed
- `upsert_products` validates and merges values through the same path as the REST PATCH endpoint, so associations survive partial updates, unknown option codes are rejected and every locale in a payload is stored ([#4](https://github.com/unopim/unopim-mcp/issues/4)).
- `IN` / `NOT IN` filters accept a comma-separated string as well as an array ([#5](https://github.com/unopim/unopim-mcp/issues/5)).
- The product and attribute upserts no longer leave a database transaction open when they return an error early.
- A consent view the host application already bound is no longer overridden.

### Changed
- Requires `laravel/mcp` `^0.7.1`, where the JsonRpc classes moved; older releases failed every `tools/call`.
- `upsert_products` now rejects values the REST API rejects, for example `common.name` when `name` is channel and locale scoped.
- Translations ship for all 33 UnoPim locales.

### Upgrade notes
- Admins on custom roles need the matching ACL permission per tool; `settings` is no longer a blanket grant.
- Set `MCP_REMOTE_DEVELOPER_TOOLS=true` to keep serving developer tools to remote clients.
- Stock UnoPim Docker/Nginx: allow `/.well-known/` above the hidden-file rule, see the README ([#16](https://github.com/unopim/unopim-mcp/issues/16)).

## [1.0.2] - 2026-09-04

### Fixed
- UnoPim 3 / Passport 13: the package ships and registers its own OAuth consent screen and moves Passport onto the `admin` guard, fixing the HTTP 500 on `/oauth/authorize` ([#13](https://github.com/unopim/unopim-mcp/issues/13)).
- Remote MCP clients stay connected: an expired or missing token answers 401 with a `WWW-Authenticate` challenge instead of a login redirect.
- All 25 shipped tools are registered ([#6](https://github.com/unopim/unopim-mcp/issues/6)).
- Annotations on two read-only tools.

### Changed
- Supports Laravel 13 and requires `laravel/passport` `^13.0`.

## [1.0.1] - 2026-06-12

### Added
- Self-contained Passport OAuth integration for remote MCP clients, including the claude.ai custom connector flow.

### Fixed
- The guard bridge is skipped when the `api` or `admin` guard is undefined.

## [1.0.0] - 2026-04-17

### Added

#### Core MCP Server
- **UnoPimAgentServer**: Main MCP server with dual transport support (HTTP SSE at `POST /api/mcp/unopim` and STDIO via `php artisan mcp:start unopim-dev`).
- **PimCallTool**: Authorization proxy with rate limiting, ACL mapping, authentication enforcement, and audit logging for all tool calls.
- **BaseMcpTool**: Unified abstract base class for standardized error handling, logging, and JSON-RPC compliance.
- **MCPServiceProvider**: Full DI container registration for all services, config publishing, route loading, and command registration.

#### Catalog Tools (8 tools)
- **CatalogSchemaTool** (`get_catalog_schema`): Returns filterable fields, supported operators, and pagination info per entity type.
- **ProductSearchTool** (`search_products`): Cursor-paginated product search with generic filters (max 100 per page).
- **ProductGetTool** (`get_product`): Fetch full product details by ID or SKU with relationships and completeness score.
- **ProductUpsertTool** (`upsert_products`): Batch create/update products (max 50 per call, atomic transaction).
- **CategorySearchTool** (`search_categories`): Cursor-paginated category search with generic filters.
- **CategoryUpsertTool** (`upsert_categories`): Batch create/update categories (max 50 per call, atomic transaction).
- **AttributeSearchTool** (`search_attributes`): Cursor-paginated attribute search with generic filters.
- **AttributeUpsertTool** (`upsert_attributes`): Batch create/update attributes (max 50 per call, atomic transaction).

#### Settings Tools (2 tools)
- **SettingSearchTool** (`search_settings`): Search channels or locales with generic filters.
- **SettingUpsertTool** (`upsert_settings`): Create/update channels or locales (max 50 per call).

#### Developer Tools (2 core + dynamic)
- **DevToolsTool** (`dev_tools`): Unified action tool with 6 actions: `create_file`, `read_file`, `update_file`, `run_command`, `generate_plugin`, `generate_test`.
- **RunSkillTool** (`run_skill`): Execute predefined skills from `.ai/skills/` by name.
- **DynamicSkillTool**: Dynamically created from SKILL.md files, auto-registered as `execute_<skill_name>`.

#### Services
- **UnoPimQueryBuilder**: Generic query builder supporting 9 filter operators (`=`, `!=`, `IN`, `NOT IN`, `CONTAINS`, `STARTS WITH`, `ENDS WITH`, `>`, `<`) with cursor-based pagination.
- **SkillLoader**: Recursive filesystem scanner for `.ai/skills/` with caching, duplicate detection, and path safety validation.
- **SkillParser**: YAML frontmatter + markdown body parser for SKILL.md files with defaults for missing fields.
- **SkillExecutor**: Coordinator delegating file, command, plugin, test, and skill operations to underlying DevTools services.

#### DevTools
- **FileManager**: File CRUD with path traversal protection (`.`/`..` normalization, `allowed_paths` jailing).
- **CommandRunner**: Whitelisted command execution (only `php artisan` and `composer`), shell operator blocking (`;`, `&`, `|`, `` ` ``, `$()`, `<`, `>`), array-based Process execution.
- **PluginGenerator**: Full UnoPim plugin scaffolding for 3 types (`connector`, `core-extension`, `generic`) with 15+ stub files including models, repositories, controllers, views, migrations, and config.
- **TestGenerator**: Pest test file generation for any class with proper namespace resolution.

#### MCP Resources & Prompts
- **CatalogSchemaResource** (`catalog-schema`): High-level catalog summary with product, category, and attribute counts.
- **CatalogAnalysisPrompt** (`analyze-catalog`): Guided analysis prompt for completeness, consistency, and optimization.

#### Artisan Commands
- **SetupCommand** (`mcp:install`): OAuth scaffolding via Passport, config publishing, cache clearing.
- **PluginMakeCommand** (`mcp:make`): Unified CLI for plugin and test generation.
- **DevMcpCommand** (`mcp:dev`): Alias for `mcp:start unopim-dev` (STDIO server).
- **MCPInspectorCommand** (`mcp:inspector`): Launch MCP Inspector debugger for HTTP and STDIO testing.

#### Configuration
- `config/mcp.php` with configurable: `api_auth`, `rate_limit`, `allowed_paths`, `audit_logging`, `skills_path`, `enable_cache`, `cache_ttl`, and `media` (allowed extensions and MIME types).

#### Security
- **Request Authentication**: HTTP endpoints default to `auth:api` (OAuth2 via Laravel Passport).
- **Rate Limiting**: Configurable per-minute limit per tool per client IP (default: 60 req/min).
- **Path Traversal Guard**: All file operations jailed within configured `allowed_paths`.
- **Command Whitelisting**: Only `php artisan` and `composer` allowed, shell operators blocked.
- **ACL Mapping**: Every MCP tool mapped to internal UnoPim permissions via `bouncer()`. CLI bypasses ACL for local development.
- **Audit Logging**: All destructive operations logged with user ID, IP, tool name, and arguments.

#### Test Suite
- **Pest Configuration**: `tests/Pest.php` binding all tests to `MCPTestCase`.
- **MCPTestCase**: Base test class disabling API auth by default.
- **Unit Tests (12 files)**:
  - `UnoPimQueryBuilderTest` — All 9 filter operators, chaining, validation errors, case-insensitive matching, edge cases (16 tests).
  - `FileManagerTest` — Directory auto-creation, duplicate file errors, path traversal vectors, temp directory, relative paths (10 tests).
  - `CommandRunnerTest` — Allowed/blocked commands, shell operator injection, empty command, quoted arguments (13 tests).
  - `PluginGeneratorTest` — All 3 plugin types, file verification, composer.json validation, StudlyCase, migration naming, error cases (9 tests).
  - `TestGeneratorTest` — Simple/nested class generation, namespace correctness, duplicate prevention (3 tests).
  - `SkillExecutorTest` — Full delegation for all 7 interface methods, exception propagation (11 tests).
  - `SkillLoaderTest` — Skill loading, normalization, tool key assignment (2 tests).
  - `SkillLoaderAdvancedTest` — Find by key/name, empty dirs, duplicate conflicts, cache bypass, recursive scanning, unparseable file skipping (12 tests).
  - `SkillParserTest` — Valid frontmatter parsing, fallback defaults (2 tests).
  - `SkillParserAdvancedTest` — Nonexistent file error, parameters/metadata parsing, minimal/multiline content, edge cases (10 tests).
  - `PimCallToolTest` — Rate limiting enforcement, execution when allowed, CLI context bypass (3 tests).
  - `PimCallToolAdvancedTest` — Rate limit response structure, configurable limits, audit logging for write vs read-only tools, permission mapping (8 tests).
  - `ToolRegistryTest` — All 12 tools verified, group checks, class existence, ordering (6 tests).
- **Feature Tests (11 files)**:
  - `CatalogToolsTest` — Schema discovery, product/attribute/category search (4 tests).
  - `CatalogCRUDTest` — Product/category/attribute create, update, search lifecycle via upsert tools (3 tests).
  - `CatalogRelationshipTest` — Product relationships and completeness via upsert/get tools (2 tests).
  - `BulkToolsTest` — Bulk create/update via upsert, batch limit enforcement (3 tests).
  - `SettingsToolsTest` — Channel/locale search, upsert lifecycle, invalid type rejection (5 tests).
  - `SecurityTest` — Unauthorized tool blocking, path traversal protection, valid path access (4 tests).
  - `MCPDevToolsFeatureTest` — All 6 dev_tools actions via mocked executor, error handling (8 tests).
  - `DevToolsAdvancedTest` — Test generation, skill execution, error handling, plugin type variants (4 tests).
  - `DynamicSkillToolTest` — Dynamic skill naming, description, filesystem registration (3 tests).
  - `DevCommandsTest` — mcp:install, mcp:make plugin/test, invalid action, mcp:inspector (5 tests).
  - `DataTransferToolsTest` — Command execution via dev_tools (1 test).
  - `MediaToolsTest` — Config validation for allowed extensions and MIME types (2 tests).
