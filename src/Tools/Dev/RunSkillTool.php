<?php

namespace Webkul\MCP\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Webkul\MCP\Contracts\SkillExecutorInterface;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsDestructive]
#[IsOpenWorld]
class RunSkillTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Load a skill from .ai/skills by name or tool key and return its instructions together with the provided input.';

    /**
     * The tool's title.
     */
    protected string $title = 'Run skill';

    /**
     * The tool's name.
     */
    public string $name = 'run_skill';

    public function __construct(
        protected SkillExecutorInterface $skillExecutor
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'skill_name' => ['required', 'string'],
            'input' => ['nullable', 'array'],
        ]);

        try {
            $result = $this->skillExecutor->executeSkill(
                $validated['skill_name'],
                $validated['input'] ?? []
            );
        } catch (\RuntimeException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json([
            'success' => true,
            'result' => $result,
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'skill_name' => $schema->string()
                ->description('The name of the skill to execute (e.g., bulk_product_import).')->required(),
            'input' => $schema->object()
                ->description('Input parameters for the skill.'),
        ];
    }
}
