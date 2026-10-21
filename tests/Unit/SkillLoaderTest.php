<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Webkul\MCP\Services\SkillLoader;
use Webkul\MCP\Services\SkillParser;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/mcp_skills_test_'.uniqid();
    mkdir($this->tempDir);

    Config::set('mcp.skills_path', $this->tempDir);
    Config::set('mcp.enable_cache', false);
});

afterEach(function () {
    array_map('unlink', glob("{$this->tempDir}/*/*"));
    array_map('rmdir', glob("{$this->tempDir}/*"));
    rmdir($this->tempDir);
});

it('loads and normalizes skills from the configured directory', function () {
    $skillDir = $this->tempDir.'/test-plugin';
    mkdir($skillDir);
    file_put_contents($skillDir.'/SKILL.md', "---\nname: Test Plugin\ndescription: Test desc\n---\nBody");

    $loader = new SkillLoader(app(SkillParser::class));
    $skills = $loader->all();

    expect($skills)->toHaveCount(1);
    expect($skills)->toHaveKey('test_plugin');
    expect($skills['test_plugin']['name'])->toBe('Test Plugin');
    expect($skills['test_plugin']['tool_key'])->toBe('test_plugin');
});

it('caches loaded skills if cache is enabled', function () {
    Config::set('mcp.enable_cache', true);
    Cache::shouldReceive('remember')->once()->andReturn([
        'cached_skill' => [
            'name' => 'Cached',
            'tool_key' => 'cached_skill',
            'description' => 'A cached skill',
        ],
    ]);

    $loader = new SkillLoader(app(SkillParser::class));
    $skills = $loader->all();

    expect($skills)->toHaveKey('cached_skill');
});
