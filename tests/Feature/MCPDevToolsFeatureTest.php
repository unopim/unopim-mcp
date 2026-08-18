<?php

namespace Webkul\MCP\Tests\Feature;

use Webkul\MCP\Contracts\SkillExecutorInterface;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Dev\DevToolsTool;

afterEach(function () {
    \Mockery::close();
});

it('creates a file via dev_tools action', function () {
    $executor = \Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('createFile')->once()->with('test.txt', 'hello')->andReturn(true);

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'create_file',
        'params' => ['path' => 'test.txt', 'content' => 'hello'],
    ])->assertOk();
});

it('reads a file via dev_tools action', function () {
    $executor = \Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('readFile')->once()->with('test.txt')->andReturn('file content here');

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'read_file',
        'params' => ['path' => 'test.txt'],
    ])->assertOk()->assertSee('file content here');
});

it('updates a file via dev_tools action', function () {
    $executor = \Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('updateFile')->once()->with('test.txt', 'updated')->andReturn(true);

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'update_file',
        'params' => ['path' => 'test.txt', 'content' => 'updated'],
    ])->assertOk();
});

it('runs a command via dev_tools action', function () {
    $executor = \Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('runCommand')->once()->with('php artisan list')->andReturn('artisan output');

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'run_command',
        'params' => ['command' => 'php artisan list'],
    ])->assertOk()->assertSee('artisan output');
});

it('generates a plugin via dev_tools action', function () {
    $executor = \Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('generatePlugin')->once()->with('CoolPlugin', 'connector')->andReturn([
        'name' => 'CoolPlugin',
        'message' => 'Plugin [CoolPlugin] generated successfully.',
        'files' => [],
    ]);

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'generate_plugin',
        'params' => ['name' => 'CoolPlugin', 'type' => 'connector'],
    ])->assertOk()->assertSee('CoolPlugin');
});

it('generates a test via dev_tools action', function () {
    $executor = \Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('generateTest')->once()->with('Webkul/MyPlugin', 'Services/MyService')
        ->andReturn('packages/Webkul/MyPlugin/tests/Unit/Services/MyServiceTest.php');

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'generate_test',
        'params' => ['package' => 'Webkul/MyPlugin', 'class' => 'Services/MyService'],
    ])->assertOk()->assertSee('MyServiceTest.php');
});

it('returns error for invalid action', function () {
    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'invalid_action',
        'params' => [],
    ])->assertHasErrors();
});

it('returns error when action throws exception', function () {
    $executor = \Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('runCommand')->andThrow(new \RuntimeException('Command not allowed: rm'));

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'run_command',
        'params' => ['command' => 'rm -rf /'],
    ])->assertHasErrors(['Command not allowed']);
});
