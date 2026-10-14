<?php

use Webkul\MCP\Contracts\FileManagerInterface;
use Webkul\MCP\DevTools\CommandRunner;
use Webkul\MCP\DevTools\PluginGenerator;
use Webkul\MCP\DevTools\TestGenerator;
use Webkul\MCP\Services\SkillExecutor;
use Webkul\MCP\Services\SkillLoader;

beforeEach(function () {
    $this->fileManager = Mockery::mock(FileManagerInterface::class);
    $this->commandRunner = Mockery::mock(CommandRunner::class);
    $this->pluginGenerator = Mockery::mock(PluginGenerator::class);
    $this->testGenerator = Mockery::mock(TestGenerator::class);

    $this->executor = new SkillExecutor(
        $this->fileManager,
        $this->commandRunner,
        $this->pluginGenerator,
        $this->testGenerator
    );
});

afterEach(function () {
    Mockery::close();
});

it('delegates createFile to FileManager', function () {
    $this->fileManager->shouldReceive('create')
        ->once()
        ->with('path/to/file.txt', 'content here')
        ->andReturn(true);

    expect($this->executor->createFile('path/to/file.txt', 'content here'))->toBeTrue();
});

it('delegates readFile to FileManager', function () {
    $this->fileManager->shouldReceive('read')
        ->once()
        ->with('path/to/file.txt')
        ->andReturn('file contents');

    expect($this->executor->readFile('path/to/file.txt'))->toBe('file contents');
});

it('delegates updateFile to FileManager', function () {
    $this->fileManager->shouldReceive('update')
        ->once()
        ->with('path/to/file.txt', 'updated content')
        ->andReturn(true);

    expect($this->executor->updateFile('path/to/file.txt', 'updated content'))->toBeTrue();
});

it('delegates runCommand to CommandRunner', function () {
    $this->commandRunner->shouldReceive('run')
        ->once()
        ->with('php artisan migrate')
        ->andReturn('Migration successful');

    expect($this->executor->runCommand('php artisan migrate'))->toBe('Migration successful');
});

it('delegates generatePlugin to PluginGenerator with default type', function () {
    $this->pluginGenerator->shouldReceive('generate')
        ->once()
        ->with('MyPlugin', 'connector')
        ->andReturn(['name' => 'MyPlugin', 'message' => 'generated']);

    $result = $this->executor->generatePlugin('MyPlugin');

    expect($result['name'])->toBe('MyPlugin');
});

it('delegates generatePlugin to PluginGenerator with custom type', function () {
    $this->pluginGenerator->shouldReceive('generate')
        ->once()
        ->with('MyPlugin', 'generic')
        ->andReturn(['name' => 'MyPlugin', 'message' => 'generated']);

    $result = $this->executor->generatePlugin('MyPlugin', 'generic');

    expect($result['name'])->toBe('MyPlugin');
});

it('delegates generateTest to TestGenerator', function () {
    $this->testGenerator->shouldReceive('generate')
        ->once()
        ->with('Webkul/MCP', 'Services/SkillLoader')
        ->andReturn('packages/Webkul/MCP/tests/Unit/Services/SkillLoaderTest.php');

    $result = $this->executor->generateTest('Webkul/MCP', 'Services/SkillLoader');

    expect($result)->toContain('SkillLoaderTest.php');
});

it('renders a known skill with its input', function () {
    $loader = Mockery::mock(SkillLoader::class);
    $loader->shouldReceive('find')->once()->with('my-skill')->andReturn([
        'name' => 'my-skill',
        'description' => 'Does things',
        'content' => 'Step one.',
    ]);

    $executor = new SkillExecutor($this->fileManager, $this->commandRunner, $this->pluginGenerator, $this->testGenerator, $loader);

    expect($executor->executeSkill('my-skill', ['arg1' => 'val1']))
        ->toContain('# Skill: my-skill')
        ->toContain('Step one.')
        ->toContain('"arg1": "val1"');
});

it('throws for a skill that does not exist', function () {
    $loader = Mockery::mock(SkillLoader::class);
    $loader->shouldReceive('find')->once()->with('empty-skill')->andReturn(null);

    $executor = new SkillExecutor($this->fileManager, $this->commandRunner, $this->pluginGenerator, $this->testGenerator, $loader);

    expect(fn () => $executor->executeSkill('empty-skill'))
        ->toThrow(RuntimeException::class, 'empty-skill');
});

it('propagates FileManager exceptions', function () {
    $this->fileManager->shouldReceive('read')
        ->once()
        ->andThrow(new RuntimeException('Security: Access restricted'));

    expect(fn () => $this->executor->readFile('../../etc/passwd'))
        ->toThrow(RuntimeException::class, 'Security');
});

it('propagates CommandRunner exceptions', function () {
    $this->commandRunner->shouldReceive('run')
        ->once()
        ->andThrow(new RuntimeException('Command not allowed'));

    expect(fn () => $this->executor->runCommand('rm -rf /'))
        ->toThrow(RuntimeException::class, 'Command not allowed');
});
