<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Webkul\MCP\Services\SkillLoader;
use Webkul\MCP\Services\SkillParser;
use Webkul\MCP\Tools\Dev\DynamicSkillTool;

it('creates a dynamic skill tool with correct name', function () {
    $skillData = [
        'name' => 'test-skill',
        'description' => 'A test skill for automation.',
        'content' => "# Test Skill Content\n\nThese are instructions.",
    ];

    $tool = new DynamicSkillTool($skillData);

    expect($tool->name())->toBe('execute_test_skill');
    expect($tool->description())->toBe('A test skill for automation.');
});

it('creates a dynamic skill tool with default description', function () {
    $skillData = [
        'name' => 'my-tool',
    ];

    $tool = new DynamicSkillTool($skillData);

    expect($tool->name())->toBe('execute_my_tool');
    expect($tool->description())->toContain('my-tool');
});

it('loads dynamic skills from filesystem via SkillLoader', function () {
    $tempDir = sys_get_temp_dir().'/mcp_dyn_test_'.uniqid();
    mkdir($tempDir);
    mkdir($tempDir.'/my-skill');
    file_put_contents($tempDir.'/my-skill/SKILL.md', "---\nname: my-skill\ndescription: Test dynamic loading\n---\n# Instructions\nDo something.");

    Config::set('mcp.skills_path', $tempDir);
    Config::set('mcp.enable_cache', false);

    // Use a fresh SkillLoader to verify skill discovery
    $loader = new SkillLoader(new SkillParser);
    $skills = $loader->all();

    expect($skills)->toHaveKey('my-skill');
    expect($skills['my-skill']['name'])->toBe('my-skill');

    // Verify the DynamicSkillTool can be created from the loaded skill
    $tool = new DynamicSkillTool($skills['my-skill']);
    expect($tool->name())->toBe('execute_my_skill');

    File::deleteDirectory($tempDir);
});
