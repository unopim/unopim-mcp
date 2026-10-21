<?php

namespace Webkul\MCP\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\MCP\Services\SkillRenderer;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class DynamicSkillTool extends BaseMcpTool
{
    public function __construct(
        protected array $skillData,
        protected SkillRenderer $renderer = new SkillRenderer,
    ) {}

    /**
     * The tool name. A skill name that is already a valid MCP tool name keeps its
     * existing form; anything else falls back to the normalised `tool_key`, so the
     * result always matches `^[a-zA-Z0-9_-]{1,64}$`.
     */
    public function name(): string
    {
        $name = 'execute_'.str_replace('-', '_', (string) ($this->skillData['name'] ?? ''));

        if ($name !== 'execute_' && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $name)) {
            return $name;
        }

        $key = $this->skillData['tool_key'] ?? $this->skillData['name'] ?? '';
        $key = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower((string) $key)), '_');

        return substr('execute_'.($key === '' ? 'skill' : $key), 0, 64);
    }

    public function title(): string
    {
        return 'Run '.Str::headline((string) ($this->skillData['name'] ?? '')).' skill';
    }

    public function description(): string
    {
        return $this->skillData['description'] ?? 'Execute the '.$this->skillData['name'].' skill.';
    }

    protected function execute(Request $request): Response
    {
        return Response::text($this->renderer->render($this->skillData));
    }

    public function schema(JsonSchema $schema): array
    {
        $properties = [];

        foreach (($this->skillData['parameters']['properties'] ?? []) as $paramName => $paramData) {
            $type = strtolower($paramData['type'] ?? 'string');
            $desc = $paramData['description'] ?? '';

            if ($type === 'string') {
                $prop = $schema->string()->description($desc);
            } elseif ($type === 'integer' || $type === 'number') {
                $prop = $schema->integer()->description($desc);
            } elseif ($type === 'boolean') {
                $prop = $schema->boolean()->description($desc);
            } else {
                $prop = $schema->string()->description($desc);
            }

            if (in_array($paramName, $this->skillData['parameters']['required'] ?? [])) {
                $prop->required();
            }

            $properties[$paramName] = $prop;
        }

        return $properties;
    }
}
