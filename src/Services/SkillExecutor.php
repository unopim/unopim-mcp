<?php

namespace Webkul\MCP\Services;

use Webkul\MCP\Contracts\FileManagerInterface;
use Webkul\MCP\Contracts\SkillExecutorInterface;
use Webkul\MCP\DevTools\CommandRunner;
use Webkul\MCP\DevTools\PluginGenerator;
use Webkul\MCP\DevTools\TestGenerator;

class SkillExecutor implements SkillExecutorInterface
{
    public function __construct(
        protected FileManagerInterface $fileManager,
        protected CommandRunner $commandRunner,
        protected PluginGenerator $pluginGenerator,
        protected TestGenerator $testGenerator,
        protected ?SkillLoader $skillLoader = null,
        protected SkillRenderer $skillRenderer = new SkillRenderer,
    ) {}

    public function createFile(string $path, string $content): bool
    {
        return $this->fileManager->create($path, $content);
    }

    public function readFile(string $path): string
    {
        return $this->fileManager->read($path);
    }

    public function updateFile(string $path, string $content): bool
    {
        return $this->fileManager->update($path, $content);
    }

    public function runCommand(string $command): string
    {
        return $this->commandRunner->run($command);
    }

    public function generatePlugin(string $name, string $type = 'connector'): array
    {
        return $this->pluginGenerator->generate($name, $type);
    }

    public function generateTest(string $packageName, string $className): string
    {
        return $this->testGenerator->generate($packageName, $className);
    }

    /**
     * Render a skill's instructions together with the given input.
     *
     * @throws \RuntimeException when no skill matches the name or tool key.
     */
    public function executeSkill(string $skillName, array $args = []): string
    {
        $skill = ($this->skillLoader ?? app(SkillLoader::class))->find($skillName);

        if ($skill === null) {
            throw new \RuntimeException(trans('mcp::app.dev.run-skill.not-found', ['name' => $skillName]));
        }

        return $this->skillRenderer->render($skill, $args);
    }
}
