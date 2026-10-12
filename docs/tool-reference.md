# Tool Reference

Full list of every MCP tool exposed by the UnoPim bridge, with parameters, defaults, and permission mapping. All tools are registered through `Webkul\MCP\Registry\ToolRegistry` and routed via `Webkul\MCP\Servers\Methods\PimCallTool`, which applies authentication, rate limiting, ACL, and audit logging.

## Common Conventions

- **Batch limit**: All `upsert_*` tools accept up to **50 items** per call and run inside a single atomic DB transaction.
- **Pagination**: All `search_*` tools are cursor-paginated — pass `cursor` from a previous response to fetch the next page. Max `limit` is **100**; default is **25**.
- **Filters**: `search_*` tools accept a `filters` array of `{field, operator, value}` entries. See [Query Operators](#query-operators) below.
- **Errors**: Unhandled exceptions return a sanitized `Response::error(...)` with a reference ID; full traces go to the Laravel log.

---

## Catalog Tools

### `get_catalog_schema`
Returns filterable fields, supported operators, and pagination rules. Always call this first so the AI can discover your instance-specific attribute configuration.

- Parameters: none.
- Response: `{ filterable_fields, operators, pagination }`.
- Permission: `catalog`.

### `search_products`
Cursor-paginated product search.

| Field | Type | Default | Notes |
|---|---|---|---|
| `filters` | `array` | `[]` | List of `{field, operator, value}`. |
| `limit` | `integer` | `25` | Max `100`. |
| `cursor` | `string` | `null` | Cursor from a previous response. |

Permission: `catalog.products`.

### `get_product`
Fetch a single product by ID or SKU, with `attribute_family`, `parent`, `super_attributes`, and `variants` relationships plus completeness score.

| Field | Type | Notes |
|---|---|---|
| `identifier` | `string` (required) | Numeric ID or SKU. |

Permission: `catalog.products`.

### `upsert_products`
Batch create or update products. Creation requires `type` and `attribute_family_id`; updates only need `sku`.

| Field | Type | Notes |
|---|---|---|
| `products` | `array` (required, 1–50) | Batch of product payloads. |
| `products.*.sku` | `string` (required) | Unique identifier. |
| `products.*.type` | `string` | `simple`, `configurable`, `virtual`, `downloadable`, `bundle`, `grouped`. Required on create. |
| `products.*.attribute_family_id` | `integer` | Required on create. |
| `products.*.values` | `object` | Map of attribute values. |

Permission: `catalog.products.create`.

### `search_categories` / `upsert_categories`
Standard search/upsert on categories, keyed by `code`. Upsert accepts `code` (required), `name`, `parent_id`, `status`, `additional_data`.

Permissions: `catalog.categories`, `catalog.categories.create`.

### `search_attributes` / `upsert_attributes`
Standard search/upsert on attributes. Upsert requires `type` when creating new attributes.

Upsert fields: `code` (required), `type`, `name`, `is_required`, `is_unique`, `value_per_locale`, `value_per_channel`, `options[]`.

Permissions: `catalog.attributes`, `catalog.attributes.create`.

### `search_families` / `upsert_families`
Manage attribute families. Upsert fields: `code` (required), `name`.

### `search_attribute_groups` / `upsert_attribute_groups`
Manage attribute groups. Upsert fields: `code` (required), `name`.

---

## Settings Tools

### `search_settings`
Search channels or locales. Pass `type`: `channels` or `locales`.

### `upsert_settings`
Create or update channels/locales in a single atomic call. Pass `type` plus an `items[]` array of `{code, ...extra}`.

### `search_currencies` / `upsert_currencies`
Manage currencies. Upsert fields: `code` (required, 3-letter ISO), `name`, `symbol`, `status`.

Note: the `name` field is derived from the ISO code by the Currency model accessor — storing it has no effect, but it is accepted for forward compatibility.

---

## Data Transfer Tools

### `search_jobs`
Search import/export job instances. Filterable fields: `code`, `type`, `entity_type`, `action`.

### `get_job_execution`
Fetch a single job execution (JobTrack) by ID. Response includes `state`, `processed_rows_count`, `invalid_rows_count`, `errors_count`, `summary`, `started_at`, `completed_at`, and `errors`.

| Field | Type | Notes |
|---|---|---|
| `id` | `integer` (required) | JobTrack ID. |

---

## Developer Tools

### `dev_tools`
Unified developer operations tool dispatched by `action`.

| Action | Params | Description |
|---|---|---|
| `create_file` | `path`, `content` | Create a new file inside an allowed path. |
| `read_file` | `path` | Read a file inside an allowed path. |
| `update_file` | `path`, `content` | Overwrite a file. |
| `run_command` | `command` | Run a whitelisted `php artisan` or `composer` command. Shell operators are blocked. |
| `generate_plugin` | `name`, `type` | `type` is one of `connector`, `core-extension`, `generic`. Default `connector`. |
| `generate_test` | `package`, `class` | Generate a Pest test skeleton for a class. |

Permission: `settings`.

### `run_skill`
Execute a predefined skill by name.

| Field | Type | Notes |
|---|---|---|
| `skill_name` | `string` (required) | Skill identifier (case-insensitive). |
| `input` | `object` | Arbitrary input passed through. |

Permission: `settings`.

### Dynamic Skills — `execute_<skill_name>`
Each `SKILL.md` in the configured skills path (default `.ai/skills/`) is registered as its own tool. The tool name is the skill's normalized snake_case name prefixed with `execute_`. See [Extending the Bridge](extending-mcp.md) for the SKILL.md format.

---

## Resources & Prompts

| Name | Type | Description |
|---|---|---|
| `catalog-schema` | Resource | High-level catalog summary. |
| `analyze-catalog` | Prompt | Guided catalog analysis workflow. |

---

## Query Operators

Used by every `search_*` tool via the `filters` parameter.

| Operator | SQL equivalent | Example |
|---|---|---|
| `=` | `field = ?` | `{"field": "status", "operator": "=", "value": "active"}` |
| `!=` | `field != ?` | `{"field": "type", "operator": "!=", "value": "bundle"}` |
| `IN` | `field IN (...)` | `{"field": "type", "operator": "IN", "value": ["simple", "configurable"]}` |
| `NOT IN` | `field NOT IN (...)` | `{"field": "id", "operator": "NOT IN", "value": [1, 2]}` |
| `CONTAINS` | `field LIKE %?%` | `{"field": "name", "operator": "CONTAINS", "value": "shirt"}` |
| `STARTS WITH` | `field LIKE ?%` | `{"field": "sku", "operator": "STARTS WITH", "value": "PRD"}` |
| `ENDS WITH` | `field LIKE %?` | `{"field": "sku", "operator": "ENDS WITH", "value": "001"}` |
| `>` | `field > ?` | `{"field": "price", "operator": ">", "value": 100}` |
| `<` | `field < ?` | `{"field": "stock", "operator": "<", "value": 5}` |

Operators are matched case-insensitively. Unknown operators throw `InvalidArgumentException`.

---

## Permission Mapping

`PimCallTool::isAuthorized()` maps each tool to a UnoPim permission checked via `bouncer()`. Tools without a mapping — the developer tools and dynamic skills — require a role with full access. When the server runs under `php artisan mcp:start` (stdio) outside the `testing` environment, permission checks are bypassed — the operator already has machine access.

| Tool | Permission |
|---|---|
| `get_catalog_schema` | `catalog` |
| `search_products`, `get_product` | `catalog.products` |
| `upsert_products` | `catalog.products.create` |
| `search_categories` | `catalog.categories` |
| `upsert_categories` | `catalog.categories.create` |
| `search_attributes`, `search_attribute_options` | `catalog.attributes` |
| `upsert_attributes` | `catalog.attributes.create` |
| `search_families` | `catalog.families` |
| `upsert_families` | `catalog.families.create` |
| `search_attribute_groups` | `catalog.attribute_groups` |
| `upsert_attribute_groups` | `catalog.attribute_groups.create` |
| `search_settings`, `upsert_settings` | `settings` |
| `search_currencies` | `settings.currencies` |
| `upsert_currencies` | `settings.currencies.create` |
| `search_jobs` | `data_transfer` |
| `get_job_execution` | `data_transfer.job_tracker` |
| `dev_tools`, `run_skill`, `get_app_info`, `get_database_schema`, `run_database_query`, `read_logs`, dynamic skills | Full access role |

Over HTTP, the developer tools and dynamic skills are only served when `MCP_REMOTE_DEVELOPER_TOOLS=true`.
