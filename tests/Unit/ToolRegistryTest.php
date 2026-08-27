<?php

use Webkul\MCP\Registry\ToolRegistry;
use Webkul\MCP\Tools\Catalog\AttributeFamilySearchTool;
use Webkul\MCP\Tools\Catalog\AttributeOptionSearchTool;
use Webkul\MCP\Tools\Catalog\ProductSearchTool;
use Webkul\MCP\Tools\DataTransfer\JobSearchTool;
use Webkul\MCP\Tools\Dev\DatabaseQueryTool;
use Webkul\MCP\Tools\Dev\DevToolsTool;
use Webkul\MCP\Tools\Settings\CurrencySearchTool;

it('registers every tool class the package ships', function () {
    $registered = collect(ToolRegistry::groups())->flatten()->all();

    $shipped = collect(glob(dirname(__DIR__, 2).'/src/Tools/*/*.php'))
        ->map(fn (string $path): string => basename($path, '.php'))
        // Skills are registered per skill file by the server, not by the registry.
        ->reject(fn (string $class): bool => in_array($class, ['BaseMcpTool', 'DynamicSkillTool'], true))
        ->sort()
        ->values();

    $registeredNames = collect($registered)
        ->map(fn (string $class): string => class_basename($class))
        ->sort()
        ->values();

    expect($registeredNames->all())->toBe($shipped->all());
});

it('exposes catalog, settings and data transfer tools by default', function () {
    config(['mcp.tools' => null]);

    $tools = ToolRegistry::tools();

    expect($tools)->toContain(ProductSearchTool::class)
        ->and($tools)->toContain(AttributeOptionSearchTool::class)
        ->and($tools)->toContain(AttributeFamilySearchTool::class)
        ->and($tools)->toContain(CurrencySearchTool::class)
        ->and($tools)->toContain(JobSearchTool::class);
});

it('keeps the developer group out unless it is switched on', function () {
    config(['mcp.tools.developer' => false]);

    $tools = ToolRegistry::tools();

    expect($tools)->not->toContain(DevToolsTool::class)
        ->and($tools)->not->toContain(DatabaseQueryTool::class);
});

it('includes the developer group once enabled', function () {
    config(['mcp.tools.developer' => true]);

    $tools = ToolRegistry::tools();

    expect($tools)->toContain(DevToolsTool::class)
        ->and($tools)->toContain(DatabaseQueryTool::class);
});

it('can disable a group of catalog tools entirely', function () {
    config(['mcp.tools.catalog' => false]);

    expect(ToolRegistry::tools())->not->toContain(ProductSearchTool::class);
});
