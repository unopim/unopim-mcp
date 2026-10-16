# Tool Reference

Full list of every MCP tool exposed by the UnoPim bridge, with parameters, defaults, and permission mapping. All tools are registered through `Webkul\MCP\Registry\ToolRegistry` and routed via `Webkul\MCP\Servers\Methods\PimCallTool`, which applies authentication, rate limiting, ACL, and audit logging.

## Common Conventions

- **Batch limit**: All `upsert_*` tools accept up to **50 items** per call and run inside a single atomic DB transaction.
- **Pagination**: All `search_*` tools are cursor-paginated — pass `cursor` from a previous response to fetch the next page. Max `limit` is **100**; default is **25**.
- **Filters**: `search_*` tools accept a `filters` array of `{field, operator, value}` entries. See [Query Operators](#query-operators) below.
- **Invalid input**: An unknown filter field or operator, a list passed to a single-value operator, or a cursor that does not belong to the search returns a validation error naming the problem, not a generic failure.
- **Errors**: Unhandled exceptions return a sanitized `Response::error(...)` with a reference ID; full traces go to the Laravel log.

---

## Catalog Tools

### `get_catalog_schema`
Returns the filter fields each search tool accepts, the supported operators, and pagination rules. Always call this first so the AI can discover your instance-specific attribute configuration.

- Parameters: none.
- Response: `{ filterable_fields, placeholders, operators, pagination }`.
- Product fields are the product columns (`id`, `sku`, `type`, `status`, `attribute_family_id`, `parent_id`, `created_at`, `updated_at`) plus a JSON path for each filterable attribute, scoped the way its values are stored — for example `values->common->color`, `values->channel_locale_specific->{channel}->{locale}->name`, or `values->channel_locale_specific->{channel}->{locale}->price->{currency}`. Replace the `{channel}`, `{locale}` and `{currency}` placeholders with real codes.
- Permission: `catalog`.

### `search_products`
Cursor-paginated product search.

| Field | Type | Default | Notes |
|---|---|---|---|
| `filters` | `array` | `[]` | List of `{field, operator, value}`. |
| `limit` | `integer` | `25` | Max `100`. |
| `cursor` | `string` | `null` | Cursor from a previous response. |

Each result carries `id`, `sku`, `type`, `status` (boolean), and `attribute_family_id`.

Permission: `catalog.products`.

### `get_product`
Fetch a single product by ID or SKU, with `attribute_family`, `parent`, `super_attributes`, and `variants` relationships plus completeness score.

| Field | Type | Notes |
|---|---|---|
| `identifier` | `string` or `integer` (required) | SKU or numeric ID. A SKU match wins, so a numeric SKU is never confused with another product's ID. |

Permission: `catalog.products`.

### `upsert_products`
Batch create or update products. Creation requires `type` and `attribute_family_id` (or `parent_sku` for a variant); updates only need `sku`.

| Field | Type | Notes |
|---|---|---|
| `products` | `array` (required, 1–50) | Batch of product payloads. |
| `products.*.sku` | `string` (required) | Unique identifier. Must follow UnoPim's SKU rule (no surrounding spaces, commas or semicolons). |
| `products.*.type` | `string` | A product type registered in UnoPim that can be created directly — `simple` or `configurable` on a stock install. Required on create; ignored on update. |
| `products.*.attribute_family_id` | `integer` | Must be an existing family. Required on create; ignored on update. |
| `products.*.super_attributes` | `array` | Variant attribute codes. Required to create a `configurable` product; each must be a select attribute of the family that does not vary by locale or channel. |
| `products.*.parent_sku` | `string` | Creates the product as a `simple` variant of this configurable product, in the parent's family. `values.common` must hold an option code for every super attribute of the parent, and the combination must be unique. Parents with a two-level variant structure are not supported; use the REST API. |
| `products.*.status` | `boolean` | Enables or disables the product. |
| `products.*.values` | `object` | Map of attribute values. |

Permissions: `catalog.products.create` for new SKUs, `catalog.products.edit` for existing ones.

### `search_categories` / `upsert_categories`
Standard search/upsert on categories, keyed by `code`. Upsert accepts `code` (required), `name`, `parent_id`, `additional_data`.

- `name` is stored where UnoPim keeps category names: `additional_data.locale_specific.{locale}.name` for the requested locale. A name given in `additional_data` for that locale takes precedence.
- `parent_id` must be an existing category, and cannot be the category itself or one of its subcategories.

Permissions: `catalog.categories` to search; `catalog.categories.create` for new codes and `catalog.categories.edit` for existing ones.

### `search_attributes` / `upsert_attributes`
Standard search/upsert on attributes. Upsert requires `type` when creating new attributes.

Upsert fields: `code` (required), `type`, `name`, `is_required`, `is_unique`, `value_per_locale`, `value_per_channel`, `options[]`.

- `code` and option codes follow UnoPim's code rule (letters, digits and underscores).
- `type` must be an attribute type registered in UnoPim (`text`, `textarea`, `price`, `boolean`, `select`, `multiselect`, `datetime`, `date`, `image`, `gallery`, `file`, `checkbox` on a stock install). It cannot be changed on an existing attribute; sending a different type is rejected.
- `options[]` (`{code, label, sort_order}`) applies to `select`, `multiselect` and `checkbox` attributes and is matched by `code`: existing options are updated, new ones are created, and options left out are kept.

### `search_attribute_options`
Search attribute options. Filter fields: `id`, `attribute_id`, `code`, `sort_order`, and `label` (matches a label in any locale). Results are ordered by `sort_order`, then `id`, so options sharing a sort order page reliably.

Permissions: `catalog.attributes`, `catalog.attributes.create`.

### `search_families` / `upsert_families`
Manage attribute families. Upsert fields: `code` (required), `name`. Updating an existing family changes only its name; its attribute groups and attribute assignments are kept.

To add groups and attributes, pass `attribute_groups`: a list of `{code, attributes}` where `code` is an existing attribute group and `attributes` lists existing attribute codes. The call is additive: nothing already in the family is removed, and an attribute already in the family stays in its current group.

```json
{"items": [{"code": "apparel", "name": "Apparel", "attribute_groups": [{"code": "general", "attributes": ["sku", "name", "color"]}]}]}
```

### `search_attribute_groups` / `upsert_attribute_groups`
Manage attribute groups. Upsert fields: `code` (required), `name`.

---

## Settings Tools

### `search_settings`
Search channels or locales. Pass `type`: `channels` or `locales`. `locales` lists every locale UnoPim ships, enabled or not; filter with `[{"field": "status", "operator": "=", "value": 1}]` for the active ones.

### `upsert_settings`
Create or update channels or locales in a single atomic call. Pass `type` (`channels` or `locales`) plus an `items[]` array keyed by `code`.

| Field | Applies to | Notes |
|---|---|---|
| `code` | both | Required. Letters, digits and `_` for a new record. |
| `status` | locales | Enables or disables the locale. A locale used by a channel or user cannot be disabled. |
| `root_category_id` | channels | Id of a root category. Required for a new channel. |
| `locales` | channels | Locale codes or ids. Required for a new channel; when sent on an update it replaces the channel's locales. |
| `currencies` | channels | Currency codes or ids. Required for a new channel; when sent on an update it replaces the channel's currencies. |
| `name` | channels | Channel name for every active locale not listed in `labels`. |
| `labels` | channels | Names keyed by active locale code, e.g. `{"en_US": "Web"}`. |

Updating a channel only changes the fields you send; its locales, currencies and other translations are kept.

Permissions: `settings.channels.create` / `settings.channels.edit` or `settings.locales.create` / `settings.locales.edit`, depending on `type` and whether the code exists.

### `search_currencies` / `upsert_currencies`
Manage currencies. Upsert fields: `code` (required, 3-letter ISO), `name`, `symbol`, `status`.

Note: the `name` field is derived from the ISO code by the Currency model accessor — storing it has no effect, but it is accepted for forward compatibility.

---

## Data Transfer Tools

### `search_jobs`
Search import/export job instances. Filterable fields: `code`, `type`, `entity_type`, `action`.

Each job carries `latest_execution`: its newest run as `{id, state, started_at, completed_at}` (ISO 8601 timestamps), or `null` if it has never run. Pass `latest_execution.id` to `get_job_execution` for row counts and errors. `latest_execution` is left out when the caller lacks `data_transfer.job_tracker`, the permission `get_job_execution` needs. On PostgreSQL, `job_track` has no index on `job_instances_id` unless one is added, so the lookup scans that table on large job histories.

### `get_job_execution`
Fetch a single job execution (JobTrack) by ID (an integer from 1 to 2147483647), such as `latest_execution.id` from `search_jobs`. Response includes `state`, `processed_rows_count`, `invalid_rows_count`, `errors_count`, `summary`, `started_at`, `completed_at`, and `errors`.

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
| `run_command` | `command` | Run a whitelisted `php artisan` or `composer` command. Shell operators are blocked, as are commands that run arbitrary code: `php artisan tinker` (and its abbreviations) and `composer exec` / `composer global`. |
| `generate_plugin` | `name`, `type` | `name` is converted to StudlyCase and must then be letters and digits starting with an uppercase letter. `type` is one of `connector`, `core-extension`, `generic`. Default `connector`. |
| `generate_test` | `package`, `class` | Generate a Pest test skeleton at `packages/Webkul/{package}/tests/Unit/...`. `package` is `MyPlugin` or `Webkul/MyPlugin`; `class` is relative (`Services/MyService`) or fully qualified (`Webkul\MyPlugin\Services\MyService`). |

Permission: full access role.

### `run_skill`
Load a skill from the skills path and return its instructions, with `input` appended. An unknown skill returns an error.

| Field | Type | Notes |
|---|---|---|
| `skill_name` | `string` (required) | The skill's tool key or name (case-insensitive). |
| `input` | `object` | Arbitrary input, returned with the instructions. |

Permission: full access role.

### Dynamic Skills — `execute_<skill_name>`
Each `SKILL.md` in the configured skills path (default `.ai/skills/`) is registered as its own tool. The tool name is the skill's name prefixed with `execute_` (hyphens become underscores); a name with spaces or other characters MCP does not allow in tool names uses the normalized snake_case key instead, truncated to 64 characters. See [Extending the Bridge](extending-mcp.md) for the SKILL.md format.

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
| `=` | `field = ?` | `{"field": "status", "operator": "=", "value": true}` |
| `!=` | `field != ?` | `{"field": "type", "operator": "!=", "value": "bundle"}` |
| `IN` | `field IN (...)` | `{"field": "type", "operator": "IN", "value": ["simple", "configurable"]}` |
| `NOT IN` | `field NOT IN (...)` | `{"field": "id", "operator": "NOT IN", "value": [1, 2]}` |
| `CONTAINS` | `field LIKE %?%` | `{"field": "values->common->color", "operator": "CONTAINS", "value": "blue"}` |
| `STARTS WITH` | `field LIKE ?%` | `{"field": "sku", "operator": "STARTS WITH", "value": "PRD"}` |
| `ENDS WITH` | `field LIKE %?` | `{"field": "sku", "operator": "ENDS WITH", "value": "001"}` |
| `>` | `field > ?` | `{"field": "id", "operator": ">", "value": 100}` |
| `<` | `field < ?` | `{"field": "created_at", "operator": "<", "value": "2026-01-01"}` |

Operators are matched case-insensitively. `value` may be a string, number, boolean, or — for `IN` and `NOT IN` — an array or a comma-separated string.

A `field` must be a column of the searched table, a JSON path into one (`values->common->color`; path segments may contain only letters, digits, `_` and `-`), or a translated attribute such as `name` or `label`, which is matched against every locale. Anything else — an unknown field or operator, or an array for a single-value operator — returns a validation error listing what is accepted.

---

## Permission Mapping

`PimCallTool::isAuthorized()` maps each tool to a UnoPim permission checked via `bouncer()`. Upsert tools look up each item first: a new record needs `.create`, an existing one `.edit`, and a batch with both needs both. Tools without a mapping — the developer tools and dynamic skills — require a role with full access. When the server runs under `php artisan mcp:start` (stdio) outside the `testing` environment, permission checks are bypassed — the operator already has machine access.

| Tool | Permission |
|---|---|
| `get_catalog_schema` | `catalog` |
| `search_products`, `get_product` | `catalog.products` |
| `upsert_products` | `catalog.products.create` / `catalog.products.edit` |
| `search_categories` | `catalog.categories` |
| `upsert_categories` | `catalog.categories.create` / `catalog.categories.edit` |
| `search_attributes`, `search_attribute_options` | `catalog.attributes` |
| `upsert_attributes` | `catalog.attributes.create` / `catalog.attributes.edit` |
| `search_families` | `catalog.families` |
| `upsert_families` | `catalog.families.create` / `catalog.families.edit` |
| `search_attribute_groups` | `catalog.attribute_groups` |
| `upsert_attribute_groups` | `catalog.attribute_groups.create` / `catalog.attribute_groups.edit` |
| `search_settings` | `settings` |
| `upsert_settings` | `settings.channels.create` / `settings.channels.edit`, or `settings.locales.create` / `settings.locales.edit` |
| `search_currencies` | `settings.currencies` |
| `upsert_currencies` | `settings.currencies.create` / `settings.currencies.edit` |
| `search_jobs` | `data_transfer` |
| `get_job_execution` | `data_transfer.job_tracker` |
| `dev_tools`, `run_skill`, `get_app_info`, `get_database_schema`, `run_database_query`, `read_logs`, dynamic skills | Full access role |

Over HTTP, the developer tools and dynamic skills are only served when `MCP_REMOTE_DEVELOPER_TOOLS=true`.
