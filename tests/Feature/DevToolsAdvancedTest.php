<?php

use Webkul\MCP\Contracts\SkillExecutorInterface;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Dev\DevToolsTool;
use Webkul\MCP\Tools\Dev\RunSkillTool;

afterEach(function () {
    Mockery::close();
});

it('generates a test via dev_tools', function () {
    $executor = Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('generateTest')
        ->once()
        ->with('Webkul/MyPlugin', 'Services/MyService')
        ->andReturn('packages/Webkul/MyPlugin/tests/Unit/Services/MyServiceTest.php');

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'generate_test',
        'params' => [
            'package' => 'Webkul/MyPlugin',
            'class' => 'Services/MyService',
        ],
    ])->assertOk()->assertSee('MyServiceTest.php');
});

it('executes a skill via run_skill tool', function () {
    $executor = Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('executeSkill')
        ->once()
        ->with('bulk_import', ['file' => 'data.csv'])
        ->andReturn('Skill [bulk_import] execution triggered with 1 arguments.');

    UnoPimAgentServer::tool(RunSkillTool::class, [
        'skill_name' => 'bulk_import',
        'input' => ['file' => 'data.csv'],
    ])->assertOk()->assertSee('bulk_import');
});

it('handles file read errors gracefully via dev_tools', function () {
    $executor = Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('readFile')
        ->andThrow(new RuntimeException('Security: Access to path [../../etc/passwd] is restricted.'));

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'read_file',
        'params' => ['path' => '../../etc/passwd'],
    ])->assertHasErrors(['Security']);
});

it('handles plugin generation for different types via dev_tools', function () {
    $executor = Mockery::mock(SkillExecutorInterface::class);
    app()->instance(SkillExecutorInterface::class, $executor);
    $executor->shouldReceive('generatePlugin')
        ->once()
        ->with('MyExtension', 'core-extension')
        ->andReturn([
            'name' => 'MyExtension',
            'message' => 'Plugin [MyExtension] (core-extension) generated successfully.',
            'files' => [],
        ]);

    UnoPimAgentServer::tool(DevToolsTool::class, [
        'action' => 'generate_plugin',
        'params' => ['name' => 'MyExtension', 'type' => 'core-extension'],
    ])->assertOk()->assertSee('MyExtension');
});
