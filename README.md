# UnoPim MCP

[![Latest Version](https://img.shields.io/packagist/v/unopim/mcp.svg)](https://packagist.org/packages/unopim/mcp)
[![Pest Tests](https://github.com/unopim/unopim-mcp/actions/workflows/pest_tests.yml/badge.svg)](https://github.com/unopim/unopim-mcp/actions/workflows/pest_tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Connect ChatGPT, Claude, GitHub Copilot, Cursor, Windsurf and any other [Model Context Protocol (MCP)](https://modelcontextprotocol.io/) client to your [UnoPim](https://unopim.com) catalog.

Ask in plain language and the assistant does the work through UnoPim. Remote assistants act with the permissions of the admin who signed in.

> - *"Find every active product without an English description."*
> - *"Create a `Summer 2026` category under `Apparel` and move these 20 SKUs into it."*
> - *"Did last night's product import fail? Show me the errors."*
> - *"Scaffold a new connector plugin with tests."*

---

## Contents

- [Requirements](#requirements)
- [Install](#install)
- [Connect your AI assistant](#connect-your-ai-assistant)
- [What it can do](#what-it-can-do)
- [Security](#security)
- [Configuration](#configuration)
- [Documentation](#documentation)

---

## Requirements

| | Version |
|---|---|
| UnoPim | 3.0+ |
| PHP | 8.4.1+ |
| Laravel Passport | 13 (ships with UnoPim 3) |

UnoPim 1.x and 2.x are not supported, because they ship Passport 12.

---

## Install

From your UnoPim root:

```bash
composer require unopim/mcp
php artisan mcp:install
```

`mcp:install` runs `passport:install` if the OAuth tables are missing, publishes `config/mcp.php` and clears caches.

Check it works — this opens the MCP Inspector in your browser, where you can call every tool by hand:

```bash
php artisan mcp:inspector unopim-dev
```

---

## Connect your AI assistant

Pick the connection that matches where your assistant runs:

| | **Local (stdio)** | **Remote (HTTP + OAuth)** |
|---|---|---|
| For | Coding assistants on the machine that runs UnoPim | Chat apps and assistants on another machine |
| Clients | Claude Code, Claude Desktop, VS Code / Copilot, Cursor, Windsurf | ChatGPT, claude.ai, any remote MCP client |
| Address | `php artisan mcp:start unopim-dev` | `https://your-unopim-host/mcp/unopim` |
| Sign-in | None — runs as the local operator | UnoPim admin login (OAuth) |
| Permissions | Full access | The admin's UnoPim role |

### Local (stdio)

Replace `/path/to/unopim` with your UnoPim root.

<details>
<summary><b>Claude Code</b></summary>

Run from your UnoPim root:

```bash
claude mcp add unopim -- php artisan mcp:start unopim-dev
```
</details>

<details>
<summary><b>Claude Desktop</b> — <code>claude_desktop_config.json</code></summary>

```json
{
    "mcpServers": {
        "unopim": {
            "command": "php",
            "args": ["/path/to/unopim/artisan", "mcp:start", "unopim-dev"]
        }
    }
}
```
</details>

<details>
<summary><b>VS Code / GitHub Copilot</b> — <code>.vscode/mcp.json</code></summary>

```json
{
    "servers": {
        "unopim": {
            "type": "stdio",
            "command": "php",
            "args": ["artisan", "mcp:start", "unopim-dev"],
            "cwd": "/path/to/unopim"
        }
    }
}
```
</details>

<details>
<summary><b>Cursor</b> — <code>.cursor/mcp.json</code></summary>

```json
{
    "mcpServers": {
        "unopim": {
            "command": "php",
            "args": ["/path/to/unopim/artisan", "mcp:start", "unopim-dev"]
        }
    }
}
```
</details>

<details>
<summary><b>Windsurf</b> — <code>~/.codeium/windsurf/mcp_config.json</code></summary>

```json
{
    "mcpServers": {
        "unopim": {
            "command": "php",
            "args": ["/path/to/unopim/artisan", "mcp:start", "unopim-dev"]
        }
    }
}
```
</details>

### Remote (HTTP + OAuth)

**One-time server setup**

1. Serve UnoPim over **HTTPS** at a public address, with `APP_URL` set to it.
2. Make sure Passport keys exist. `unopim:install` creates them, so only run `php artisan passport:keys` on an install that has none — it refuses to overwrite existing keys.
3. **Nginx only:** let `/.well-known/` through. The stock UnoPim Docker `nginx.conf` blocks dot paths, so OAuth discovery answers `403`. Add this **above** the `location ~ /\.` rule and reload Nginx:

   ```nginx
   location ^~ /.well-known/ {
       try_files $uri /index.php?$query_string;
   }
   ```

   Apache needs no change.

Everything else — OAuth discovery, client registration and the consent screen — is registered by the package.

<details>
<summary><b>ChatGPT</b></summary>

1. In ChatGPT, open **Settings → Apps & Connectors → Advanced settings** and turn on **Developer mode**.
2. Back in **Apps & Connectors**, choose **Create**.
3. Enter a name and the MCP server URL `https://your-unopim-host/mcp/unopim`, set authentication to **OAuth**, and create it.
4. Sign in with your UnoPim admin account and approve access.

Custom connectors need a ChatGPT plan that offers developer mode.
</details>

<details>
<summary><b>claude.ai / Claude Desktop (remote)</b></summary>

1. Open **Settings → Connectors → Add custom connector**.
2. Enter `https://your-unopim-host/mcp/unopim`.
3. Sign in with your UnoPim admin account and approve access.
</details>

<details>
<summary><b>VS Code / GitHub Copilot (remote)</b> — <code>.vscode/mcp.json</code></summary>

```json
{
    "servers": {
        "unopim": {
            "type": "http",
            "url": "https://your-unopim-host/mcp/unopim"
        }
    }
}
```

VS Code asks you to sign in to UnoPim when it first connects.
</details>

Something not connecting? See [Troubleshooting](docs/troubleshooting.md).

---

## What it can do

25 tools, grouped below. Every tool and parameter is in the [Tool Reference](docs/tool-reference.md).

| Area | Tools | UnoPim permission |
|---|---|---|
| **Products** | `search_products`, `get_product`, `upsert_products` | `catalog.products` |
| **Categories** | `search_categories`, `upsert_categories` | `catalog.categories` |
| **Attributes** | `search_attributes`, `upsert_attributes`, `search_attribute_options`, `search_attribute_groups`, `upsert_attribute_groups`, `search_families`, `upsert_families` | `catalog.attributes`, `catalog.attribute_groups`, `catalog.families` |
| **Schema** | `get_catalog_schema` — the fields and filters each entity supports | `catalog` |
| **Settings** | `search_settings`, `upsert_settings` (channels and locales), `search_currencies`, `upsert_currencies` | `settings`, `settings.channels`, `settings.locales`, `settings.currencies` |
| **Imports & exports** | `search_jobs`, `get_job_execution` | `data_transfer` |
| **Developer** | `dev_tools` (files, Artisan/Composer, plugin and test scaffolding), `run_skill`, `get_app_info`, `get_database_schema`, `run_database_query` (read-only), `read_logs` | Full access role |

Upserts need `.create` to add a record and `.edit` to change an existing one, for example `catalog.products.create` and `catalog.products.edit`; a batch that does both needs both. The [Tool Reference](docs/tool-reference.md#permission-mapping) lists each one.

- **Search** tools filter with `=`, `!=`, `IN`, `NOT IN`, `CONTAINS`, `STARTS WITH`, `ENDS WITH`, `>`, `<`, and page with a cursor (up to 100 per page).
- **Upsert** tools create or update up to 50 records per call in one transaction — all saved, or none.
- **Skills:** drop a `SKILL.md` into `.ai/skills/<name>/` and it becomes a tool named `execute_<name>`. See [Extending](docs/extending-mcp.md).

---

## Security

- **Remote access needs a UnoPim login**, and every tool checks the admin's role permissions. Tokens of a disabled admin are refused.
- **Developer tools stay local by default.** Over HTTP they are only served when `MCP_REMOTE_DEVELOPER_TOOLS=true`, and then only to full access roles.
- **The local stdio server has full access**, the same as anyone who can run `php artisan` on that machine.
- File tools are confined to `allowed_paths`, with symlinks resolved; command tools only run `php artisan` and `composer`, with shell operators, `tinker` and `composer exec` blocked.
- `run_database_query` accepts one read statement and runs it in a read-only transaction on MySQL, MariaDB and PostgreSQL.
- Every tool call is rate limited (60 per minute per tool and user by default), and every write is logged with the user, IP and arguments.

Found a vulnerability? Please report it privately — see [SECURITY.md](SECURITY.md).

---

## Configuration

The defaults suit most installs. Override them in `.env`:

| Variable | Default | What it does |
|---|---|---|
| `MCP_API_AUTH` | `true` | Require OAuth on the HTTP endpoint. Turn off only for local testing. |
| `MCP_REMOTE_DEVELOPER_TOOLS` | `false` | Serve developer tools and skills over HTTP. |
| `MCP_RATE_LIMIT` | `60` | Calls per minute, per tool, per user (per IP for unauthenticated calls). |
| `MCP_AUDIT_LOGGING` | `true` | Log every write tool call. |
| `MCP_SKILLS_PATH` | `.ai/skills` | Where skills are loaded from. |

Every key and a production checklist: [Configuration](docs/configuration.md).

### Artisan commands

| Command | What it does |
|---|---|
| `mcp:install` | Set up Passport, publish the config, clear caches. |
| `mcp:start unopim-dev` | Start the local stdio server (`mcp:dev` is a shortcut). |
| `mcp:inspector <server>` | Open the MCP Inspector for `unopim-dev` or `mcp/unopim`. |
| `mcp:make plugin <Name>` | Scaffold a UnoPim plugin (`--type=connector\|core-extension\|generic`). |
| `mcp:make test <Package> <Class>` | Generate a Pest test for a class. |

---

## Documentation

| Guide | For |
|---|---|
| [Use Cases](docs/use-cases.md) | Example workflows for catalog managers, developers and analysts |
| [Tool Reference](docs/tool-reference.md) | Every tool, parameter, filter operator and permission |
| [Configuration](docs/configuration.md) | Every `config/mcp.php` key and a production checklist |
| [Development Workflow](docs/development-workflow.md) | Using MCP day to day while building on UnoPim |
| [Extending](docs/extending-mcp.md) | Writing skills and new tools |
| [Troubleshooting](docs/troubleshooting.md) | Connection, sign-in and tool errors |
| [Comparison](docs/comparison-and-benefits.md) | How UnoPim MCP compares with other PIM MCP servers |

---

## Contributing

Bug reports, ideas and pull requests are welcome — see [CONTRIBUTING.md](.github/CONTRIBUTING.md). Release notes are in [CHANGELOG.md](CHANGELOG.md).

## License

UnoPim MCP is open-source software licensed under the [MIT license](LICENSE).
