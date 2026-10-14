<?php

namespace Webkul\MCP\Contracts;

interface SkillExecutorInterface
{
    /**
     * Create a new file with the given content.
     */
    public function createFile(string $path, string $content): bool;

    /**
     * Read the content of a file.
     */
    public function readFile(string $path): string;

    /**
     * Update an existing file with the given content.
     */
    public function updateFile(string $path, string $content): bool;

    /**
     * Run a shell command (restricted).
     */
    public function runCommand(string $command): string;

    /**
     * Generate a new plugin.
     *
     * @return array{name: string, path: string, message: string, files: list<string>}
     */
    public function generatePlugin(string $name, string $type = 'connector'): array;

    /**
     * Generate a new test for a class.
     */
    public function generateTest(string $packageName, string $className): string;

    /**
     * Return the instructions of the named skill together with the given input.
     *
     * @throws \RuntimeException when no skill matches the name.
     */
    public function executeSkill(string $skillName, array $args = []): string;
}
