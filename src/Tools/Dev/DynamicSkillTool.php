<?php

namespace Webkul\MCP\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsDestructive]
#[IsOpenWorld]
class DynamicSkillTool extends BaseMcpTool
{
    public function __construct(
        protected array $skillData
    ) {}

    public function name(): string
    {
        return 'execute_'.str_replace('-', '_', $this->skillData['name']);
    }

    public function description(): string
    {
        return $this->skillData['description'] ?? 'Execute the '.$this->skillData['name'].' skill.';
    }

    protected function execute(Request $request): Response
    {
        $output = "# Skill: {$this->skillData['name']}\n\n";
        $output .= "{$this->skillData['description']}\n\n";
        $output .= "## Instruction Content\n\n";
        $output .= $this->skillData['content'] ?? 'No content available.';

        // Include any additional documentation files.
        $additionalDocs = [];

        if (! empty($this->skillData['files'])) {
            $additionalDocs = array_filter(array_keys($this->skillData['files']), fn ($f) => str_ends_with($f, '.md'));
        } elseif (isset($this->skillData['path'])) {
            $dir = dirname($this->skillData['path']);
            if (is_dir($dir)) {
                $files = array_diff(scandir($dir), ['.', '..', 'SKILL.md']);
                $additionalDocs = array_filter($files, fn ($f) => str_ends_with($f, '.md'));
            }
        }

        if (! empty($additionalDocs)) {
            $output .= "\n\n## Related Documentation\n";
            foreach ($additionalDocs as $doc) {
                $output .= "- {$doc}\n";
            }
            $output .= "\n*You can request content from these files if needed.*";
        }

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        $properties = [];
        $required = [];

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
