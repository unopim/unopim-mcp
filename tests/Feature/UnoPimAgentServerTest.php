<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Laravel\Mcp\Server\Transport\HttpTransport;
use Webkul\MCP\Servers\UnoPimAgentServer;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/mcp_server_test_'.uniqid();
    mkdir($this->tempDir);
    mkdir($this->tempDir.'/test-skill');
    file_put_contents($this->tempDir.'/test-skill/SKILL.md', "---\nname: Test Skill\ndescription: Test desc\n---\nBody");

    Config::set('mcp.skills_path', $this->tempDir);
    Config::set('mcp.enable_cache', false);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

it('registers all required catalog tools', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => new FakeTransporter]);

    $context = $server->createContext();
    $toolNames = $context->tools()->map(fn ($t) => $t->name())->toArray();

    expect($toolNames)->toContain('get_catalog_schema');
    expect($toolNames)->toContain('search_products');
    expect($toolNames)->toContain('get_product');
    expect($toolNames)->toContain('upsert_products');
    expect($toolNames)->toContain('search_categories');
    expect($toolNames)->toContain('upsert_categories');
    expect($toolNames)->toContain('search_attributes');
    expect($toolNames)->toContain('upsert_attributes');
});

it('registers all settings tools', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => new FakeTransporter]);

    $context = $server->createContext();
    $toolNames = $context->tools()->map(fn ($t) => $t->name())->toArray();

    expect($toolNames)->toContain('search_settings');
    expect($toolNames)->toContain('upsert_settings');
});

it('registers all dev tools', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => new FakeTransporter]);

    $context = $server->createContext();
    $toolNames = $context->tools()->map(fn ($t) => $t->name())->toArray();

    expect($toolNames)->toContain('dev_tools');
    expect($toolNames)->toContain('run_skill');
});

it('registers dynamic skills as tools', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => new FakeTransporter]);
    $context = $server->createContext();
    $toolNames = $context->tools()->map(fn ($t) => is_string($t) ? $t : $t->name())->toArray();

    // Dynamic skill names are prefixed with "execute_"
    $dynamicSkills = collect($toolNames)->filter(fn ($name) => str_starts_with($name, 'execute_'));

    expect($dynamicSkills->count())->toBeGreaterThan(0);
});

it('registers at least 12 core tools', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => new FakeTransporter]);
    $context = $server->createContext();
    $toolNames = $context->tools()->map(fn ($t) => is_string($t) ? $t : $t->name())->toArray();

    // At least 12 core tools + dynamic skills
    expect(count($toolNames))->toBeGreaterThanOrEqual(12);
});

it('registers catalog schema resource', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => new FakeTransporter]);
    $context = $server->createContext();
    $resourceNames = $context->resources()->map(fn ($r) => $r->name())->toArray();

    expect($resourceNames)->toContain('catalog-schema');
});

it('registers catalog analysis prompt', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => new FakeTransporter]);
    $context = $server->createContext();
    $promptNames = $context->prompts()->map(fn ($p) => $p->name())->toArray();

    expect($promptNames)->toContain('analyze-catalog');
});

it('leaves developer tools and dynamic skills off the remote http server by default', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => $this->createStub(HttpTransport::class)]);

    $toolNames = $server->createContext()->tools()->map(fn ($t) => $t->name())->toArray();

    expect($toolNames)->toContain('search_products')
        ->not->toContain('dev_tools')
        ->not->toContain('run_skill')
        ->not->toContain('run_database_query')
        ->not->toContain('read_logs')
        ->not->toContain('get_database_schema')
        ->not->toContain('get_app_info')
        ->and(collect($toolNames)->filter(fn ($name) => str_starts_with($name, 'execute_')))->toBeEmpty();
});

it('serves developer tools over http when the installation opts in', function () {
    Config::set('mcp.remote_developer_tools', true);

    $server = app(UnoPimAgentServer::class, ['transport' => $this->createStub(HttpTransport::class)]);

    $toolNames = $server->createContext()->tools()->map(fn ($t) => $t->name())->toArray();

    expect($toolNames)->toContain('dev_tools')->toContain('run_database_query');
});

it('leaves developer tools out of the instructions sent to remote http clients by default', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => $this->createStub(HttpTransport::class)]);

    expect($server->createContext()->instructions)
        ->toContain('search_products')
        ->toContain('search_settings')
        ->not->toContain('dev_tools')
        ->not->toContain('run_skill')
        ->not->toContain('get_app_info')
        ->not->toContain('get_database_schema')
        ->not->toContain('run_database_query')
        ->not->toContain('read_logs');
});

it('describes developer tools to remote http clients when the installation opts in', function () {
    Config::set('mcp.remote_developer_tools', true);

    $server = app(UnoPimAgentServer::class, ['transport' => $this->createStub(HttpTransport::class)]);

    expect($server->createContext()->instructions)
        ->toContain('dev_tools')
        ->toContain('run_database_query')
        ->toContain('read_logs');
});

it('describes developer tools to stdio clients', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => new FakeTransporter]);

    expect($server->createContext()->instructions)
        ->toContain('dev_tools')
        ->toContain('run_database_query')
        ->toContain('read_logs');
});

it('points clients to the tools that actually return channels, locales and attributes', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => $this->createStub(HttpTransport::class)]);

    expect($server->createContext()->instructions)
        ->not->toContain('Always start with `get_catalog_schema`')
        ->toContain('search_attributes');
});

it('sends instructions without leading indentation to remote http clients', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => $this->createStub(HttpTransport::class)]);

    expect($server->createContext()->instructions)
        ->toStartWith('This MCP server')
        ->not->toMatch('/^[ \t]+\S/m');
});

it('sends instructions without leading indentation to stdio clients', function () {
    $server = app(UnoPimAgentServer::class, ['transport' => new FakeTransporter]);

    expect($server->createContext()->instructions)
        ->toStartWith('This MCP server')
        ->toContain("\n\n### Developer Tools\n")
        ->not->toMatch('/^[ \t]+\S/m');
});
