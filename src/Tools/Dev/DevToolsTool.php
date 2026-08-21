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
class DevToolsTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Standard developer operations: file management, command execution, and code generation.';

    /**
     * The tool's name.
     */
    public string $name = 'dev_tools';

    public function __construct(
        protected SkillExecutorInterface $skillExecutor
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:create_file,read_file,update_file,run_command,generate_plugin,generate_test'],
            'params' => ['required', 'array'],
        ]);

        $action = $validated['action'];
        $params = $validated['params'];

        try {
            $result = match ($action) {
                'create_file' => $this->skillExecutor->createFile($params['path'], $params['content']),
                'read_file' => $this->skillExecutor->readFile($params['path']),
                'update_file' => $this->skillExecutor->updateFile($params['path'], $params['content']),
                'run_command' => $this->skillExecutor->runCommand($params['command']),
                'generate_plugin' => $this->skillExecutor->generatePlugin($params['name'], $params['type'] ?? 'connector'),
                'generate_test' => $this->skillExecutor->generateTest($params['package'], $params['class']),
            };

            return Response::json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->description('The action to perform.')
                ->enum(['create_file', 'read_file', 'update_file', 'run_command', 'generate_plugin', 'generate_test'])
                ->required(),
            'params' => $schema->object()
                ->description('Parameters for the action.')
                ->required(),
        ];
    }
}
