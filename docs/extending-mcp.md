# Extending the UnoPim MCP Bridge

The bridge is designed to be extensible. You can add new capabilities in two ways: **Dynamic Skills** (low-code) or **Core Tools** (PHP-based).

## 1. Creating Dynamic Skills (Low-Code)
Skills are the easiest way to extend the AI's capabilities. They are simple Markdown files that contain instructions and parameters.

### How to Create a Skill
1. Create a directory in `.ai/skills/` (e.g., `.ai/skills/bulk-image-optimizer/`).
2. Create a `SKILL.md` file inside that directory.
3. Define YAML frontmatter at the top:

```markdown
---
name: Bulk Image Optimizer
description: Optimizes product images using Intervention Image
parameters:
  quality:
    type: integer
    description: JPEG quality (1-100)
    required: true
---

# Instructions
1. Find products with images in the `public/storage` directory.
2. For each image, run the optimization logic via `dev_tools`.
3. Report the size saved.
```

The bridge automatically detects this file and registers a tool called `execute_bulk_image_optimizer`.

---

## 2. Implementing Core Tools (PHP)
If you need deep integration or custom logic that requires PHP, implement a new Core Tool.

### Standard Tool Structure
All tools should extend `Webkul\MCP\Tools\BaseMcpTool`. The base class wraps `execute()` with standardized error handling and logging, so you only need to implement the `execute()` and `schema()` methods.

```php
namespace Webkul\MCP\Tools\MyCategory;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\MCP\Tools\BaseMcpTool;

class MyCustomTool extends BaseMcpTool
{
    /**
     * The tool's name (exposed to the AI client).
     */
    public string $name = 'my_custom_tool';

    /**
     * The tool's description.
     */
    protected string $description = 'Performs a custom operation on UnoPim data.';

    /**
     * The actual tool implementation. Throw exceptions for errors — the
     * BaseMcpTool will log them with a reference ID and return a safe error
     * response to the client.
     */
    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        // Your business logic here.
        $result = ['matched' => $validated['query']];

        return Response::json($result);
    }

    /**
     * JSON schema advertised to the AI client.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The search query.')
                ->required(),
        ];
    }
}
```

> **Transactions**: If your tool performs multiple writes, wrap them in `DB::transaction(...)` or make sure every early `return Response::error(...)` is preceded by `DB::rollBack()`. Returning early inside an open transaction without a rollback will leak the transaction.

### Registering the Tool
Add your tool class to the `Webkul\MCP\Registry\ToolRegistry`.

```php
// src/Registry/ToolRegistry.php

public static function tools(): array
{
    return [
        // ... existing tools
        \Webkul\MCP\Tools\MyCategory\MyCustomTool::class,
    ];
}
```

---

## 3. Developing for the Bridge

### Security First
When adding tools that perform write operations or command execution:
- Use `FileManagerInterface` for file ops (ensures path traversal protection).
- Use `CommandRunner` for CLI ops (ensures command whitelisting).
- Always include validation for arguments.

### Testing
Every new tool should have a corresponding test in the package's `tests/` directory.
- Use **Unit Tests** for isolated logic.
- Use **Feature Tests** for end-to-end tool execution via the MCP server.
