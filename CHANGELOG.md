# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- The server instructions sent to clients only describe the developer tools, diagnostics and dynamic skills when they are served: over stdio, or over HTTP with `MCP_REMOTE_DEVELOPER_TOOLS` enabled.
- The server instructions no longer tell clients to start with `get_catalog_schema` to discover the catalog structure; they point to `search_settings` for channels and locales and `search_attributes` for attribute definitions.
- `get_job_execution` reports a missing execution with a translated message.
- `get_job_execution` rejects an `id` outside the integer column range with a validation error; on PostgreSQL such an id previously failed with a generic error.
- Remote (HTTP) MCP requests use the authenticated admin's UI locale, as the admin panel does, so translated messages match the admin's language; admins without one get the application default.
- The server instructions are sent without leading indentation, so clients no longer render them as a Markdown code block.

### Added
- `search_jobs` returns each job's `latest_execution` (`id`, `state`, `started_at`, `completed_at`, or `null` if it never ran), loaded in one query per page. Its `id` is the one `get_job_execution` takes.

## [1.0.4] - 2026-10-14

### Security
- Upsert tools check `.create` for records that do not exist yet and `.edit` for records that do, instead of `.create` for everything. A create-only role can no longer update records.
- `upsert_settings` requires `settings.channels.create|edit` or `settings.locales.create|edit` instead of the view-level `settings` permission.
- Tokens issued to a disabled admin are refused with a 401 and a `WWW-Authenticate` challenge.
- `run_database_query` accepts a single SELECT, WITH, SHOW, EXPLAIN or DESCRIBE statement, rejects `INTO`, locking clauses and sleep/file/dblink functions, and on MySQL, MariaDB and PostgreSQL runs in a read-only transaction on a dedicated connection that is always rolled back.
- `get_database_schema` lists only the application's own schema, not every database on the server.
- `dev_tools` file operations resolve symlinks and match allowed paths by whole directory, so sibling directories, symlinks pointing outside and dangling symlinks are refused.
- `dev_tools` `run_command` blocks `php artisan tinker` (and its abbreviations), `composer exec` and `composer global`.
- `generate_plugin` and `generate_test` reject names that would write outside `packages/`.
- The `.create`/`.edit` check reads each upsert tool's own item list (`products`, `categories`, `attributes`, `items`). `upsert_products`, `upsert_categories` and `upsert_attributes` used to fall back to `.create`, so a create-only role could still update existing records.
- `run_database_query` lexes comments and literals per dialect (MySQL `-- ` and `#`, PostgreSQL dollar quotes and `E''` strings), checks with and without backslash escapes, keeps quoted function names visible, and checks `SHOW`, `DESC` and `DESCRIBE` too. Queries are capped at 30 seconds on MySQL (`max_execution_time`) and MariaDB (`max_statement_time`), as on PostgreSQL.
- `upsert_families`, `upsert_attribute_groups`, `upsert_currencies` and `dev_tools` no longer return raw database or system exception messages. Unexpected errors are logged and reported with a reference.
- The OAuth authorize endpoint rejects a `code_challenge` sent with any method but `S256`, including a missing method (which League treats as `plain`), with `400 invalid_request`, as OAuth 2.1 and the MCP authorization spec require.
- Dynamic client registration (`POST oauth/register`) caps the request body (16 KB), the number of redirect URIs (10) and each redirect URI's length (2048 characters), answering `400 invalid_client_metadata`, and is rate limited to 10 registrations per minute per IP (`429`). All limits are configurable under `mcp.oauth.registration`.

### Fixed
- `upsert_currencies` refuses to disable a currency that a channel still uses, as the admin panel does, naming the currency and writing nothing from the batch.
- The OAuth authorization server metadata now advertises `token_endpoint_auth_methods_supported: ["none"]`, which ChatGPT requires before it connects. `laravel/mcp` does not publish the field; a value it publishes in a later release is kept.
- `upsert_families` no longer deletes a family's attribute groups and attribute assignments when an existing family is updated; only the name changes.
- `upsert_attributes` updates options on an existing attribute by code instead of crashing, rejects changing an existing attribute's `type`, and validates `type` against UnoPim's attribute types and codes with UnoPim's code rule.
- `upsert_categories` stores `name` as the category name for the requested locale, rejects a missing parent or a parent inside the category's own subtree, and no longer advertises an unused `status` field.
- `upsert_products` accepts only product types registered in UnoPim, requires an existing family, validates SKUs with UnoPim's SKU rule, creates configurable products with `super_attributes`, creates variants under a configurable parent with `parent_sku`, and writes `status`.
- `upsert_products` rejects changing a variant's variant attribute values to a combination a sibling variant already has.
- `upsert_products` errors name the failing item: keys read `products.{index}.values.…` and the message and payload carry its index and SKU. The batch still rolls back as a whole.
- `upsert_products` rejects prices in a currency that is not active, and prices sent as a list instead of a currency-to-amount object.
- `upsert_products` documents the `values` shape (sections, scopes, value formats and an example), and points `categories` or `associations` sent inside an attribute section, or an attribute sent in the wrong section, to where it belongs. A missing `type` or `attribute_family_id` on create is reported as a translated validation error.
- Search tools reject a filter value that is not a date for a date or timestamp column, or not a number for a numeric column, instead of silently returning no rows.
- `search_attribute_options` pages reliably when options share a sort order, and filtering by `label` works.
- Search tools return a clear validation error for an unknown filter field or operator, a list passed to a single-value operator, or an invalid cursor, instead of a generic error or a silent restart at page one. Filter fields are checked against the table's columns before they reach SQL.
- Search tools can filter on translated fields such as `name` and `label`.
- `get_catalog_schema` advertises only filter fields that `search_products` accepts, with product attribute values given as JSON paths.
- `get_product` resolves a numeric identifier by SKU before ID, accepts an integer identifier, and marks it required.
- Search tool schemas declare `filters[].value` as a string, number, boolean or list.
- `search_products` reports `status` as a boolean, consistent with `get_product`.
- `upsert_settings` saves every submitted field: locale `status`, new channels with their root category, name, locales and currencies, and partial channel updates that keep existing locales and currencies. Disabling a locale in use is rejected, as in the admin panel.
- Dynamic skill tools always get a valid MCP tool name (`^[a-zA-Z0-9_-]{1,64}$`).
- `generate_test` writes to `packages/Webkul/{package}/tests/Unit`, accepts fully qualified class names and generates a Pest test.
- `run_skill` returns the skill's instructions and input, and an error for an unknown skill instead of always reporting success.
- The permission denial message reads "Forbidden: you do not have permission to run [tool]." and is translated.

### Added
- `upsert_families` accepts `attribute_groups` (`[{code, attributes}]`) to add attribute groups and attributes to a family. It only adds, never removes, so a family built over MCP can hold the attributes its products need.

### Changed
- Rate limiting is counted per tool per authenticated user, falling back to the client IP.
- `read_logs` no longer accepts `type: mcp`; that file was never written. MCP audit entries are in the default log channel.

### Upgrade notes
- Roles that update records through MCP now need the `.edit` permission (e.g. `catalog.products.edit`); a batch that both creates and updates needs both.
- `upsert_settings` needs `settings.channels.*` or `settings.locales.*`; new channels must send `root_category_id`, `locales` and `currencies`.
- A package symlinked into `packages/` from elsewhere is outside `mcp.allowed_paths`; add its real path if `dev_tools` should edit it.

## [1.0.3] - 2026-10-12

### Security
- Every catalog, settings and data transfer tool now checks its own UnoPim ACL permission. Tools without a mapping, including developer tools and dynamic skills, require a full access role. Previously they fell back to the `catalog` permission, and any `settings` permission granted every tool.
- The remote HTTP endpoint no longer serves developer tools (`dev_tools`, `run_skill`, `get_app_info`, `get_database_schema`, `run_database_query`, `read_logs`) or dynamic skills unless `MCP_REMOTE_DEVELOPER_TOOLS=true`. Stdio (`mcp:dev`) is unchanged.

### Fixed
- `upsert_products` validates and merges values through the same path as the REST PATCH endpoint, so associations survive partial updates, unknown option codes are rejected and every locale in a payload is stored ([#4](https://github.com/unopim/unopim-mcp/issues/4)).
- `IN` / `NOT IN` filters accept a comma-separated string as well as an array ([#5](https://github.com/unopim/unopim-mcp/issues/5)).
- The product and attribute upserts no longer leave a database transaction open when they return an error early.
- A consent view the host application already bound is no longer overridden.
- `get_product` looks up by `id` only when the identifier is numeric; comparing a SKU with the integer column failed every SKU lookup on PostgreSQL.
- MCP requests are authenticated per request: `mcp.api_auth` is honoured at runtime, and a client that sends no `Accept` header receives the 401 challenge instead of a login redirect.
- `mcp:inspector` runs through the `Process` facade, so it can be faked in tests instead of launching the real Inspector.

### Changed
- Requires `laravel/mcp` `^0.7.1`, where the JsonRpc classes moved; older releases failed every `tools/call`.
- `upsert_products` now rejects values the REST API rejects, for example `common.name` when `name` is channel and locale scoped.
- Translations ship for all 33 UnoPim locales.
- CI runs Pint, Pest on MySQL, MariaDB and PostgreSQL, PHPStan and the translation check against the latest UnoPim release.

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
