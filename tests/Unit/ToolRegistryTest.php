<?php

use Webkul\MCP\Registry\ToolRegistry;
use Webkul\MCP\Tools\Catalog\AttributeFamilySearchTool;
use Webkul\MCP\Tools\Catalog\AttributeFamilyUpsertTool;
use Webkul\MCP\Tools\Catalog\AttributeGroupSearchTool;
use Webkul\MCP\Tools\Catalog\AttributeGroupUpsertTool;
use Webkul\MCP\Tools\Catalog\AttributeOptionSearchTool;
use Webkul\MCP\Tools\Catalog\AttributeSearchTool;
use Webkul\MCP\Tools\Catalog\AttributeUpsertTool;
use Webkul\MCP\Tools\Catalog\CatalogSchemaTool;
use Webkul\MCP\Tools\Catalog\CategorySearchTool;
use Webkul\MCP\Tools\Catalog\CategoryUpsertTool;
use Webkul\MCP\Tools\Catalog\ProductGetTool;
use Webkul\MCP\Tools\Catalog\ProductSearchTool;
use Webkul\MCP\Tools\Catalog\ProductUpsertTool;
use Webkul\MCP\Tools\DataTransfer\JobExecutionTool;
use Webkul\MCP\Tools\DataTransfer\JobSearchTool;
use Webkul\MCP\Tools\Dev\AppInfoTool;
use Webkul\MCP\Tools\Dev\DatabaseQueryTool;
use Webkul\MCP\Tools\Dev\DatabaseSchemaTool;
use Webkul\MCP\Tools\Dev\DevToolsTool;
use Webkul\MCP\Tools\Dev\LogReadTool;
use Webkul\MCP\Tools\Dev\RunSkillTool;
use Webkul\MCP\Tools\Settings\CurrencySearchTool;
use Webkul\MCP\Tools\Settings\CurrencyUpsertTool;
use Webkul\MCP\Tools\Settings\SettingSearchTool;
use Webkul\MCP\Tools\Settings\SettingUpsertTool;

it('returns exactly 25 registered tools', function () {
    $tools = ToolRegistry::tools();

    expect($tools)->toHaveCount(25);
});

it('contains all catalog tools', function () {
    $tools = ToolRegistry::tools();

    expect($tools)->toContain(CatalogSchemaTool::class);
    expect($tools)->toContain(ProductSearchTool::class);
    expect($tools)->toContain(ProductGetTool::class);
    expect($tools)->toContain(ProductUpsertTool::class);
    expect($tools)->toContain(CategorySearchTool::class);
    expect($tools)->toContain(CategoryUpsertTool::class);
    expect($tools)->toContain(AttributeSearchTool::class);
    expect($tools)->toContain(AttributeUpsertTool::class);
    expect($tools)->toContain(AttributeOptionSearchTool::class);
    expect($tools)->toContain(AttributeFamilySearchTool::class);
    expect($tools)->toContain(AttributeFamilyUpsertTool::class);
    expect($tools)->toContain(AttributeGroupSearchTool::class);
    expect($tools)->toContain(AttributeGroupUpsertTool::class);
});

it('contains all settings tools', function () {
    $tools = ToolRegistry::tools();

    expect($tools)->toContain(SettingSearchTool::class);
    expect($tools)->toContain(SettingUpsertTool::class);
    expect($tools)->toContain(CurrencySearchTool::class);
    expect($tools)->toContain(CurrencyUpsertTool::class);
});

it('contains all data transfer tools', function () {
    $tools = ToolRegistry::tools();

    expect($tools)->toContain(JobSearchTool::class);
    expect($tools)->toContain(JobExecutionTool::class);
});

it('contains all dev tools', function () {
    $tools = ToolRegistry::tools();

    expect($tools)->toContain(DevToolsTool::class);
    expect($tools)->toContain(RunSkillTool::class);
    expect($tools)->toContain(AppInfoTool::class);
    expect($tools)->toContain(DatabaseSchemaTool::class);
    expect($tools)->toContain(DatabaseQueryTool::class);
    expect($tools)->toContain(LogReadTool::class);
});

it('registers every tool class shipped in the package', function () {
    $shipped = collect(glob(__DIR__.'/../../src/Tools/*/*.php'))
        ->map(fn (string $path): string => basename($path, '.php'))
        // DynamicSkillTool is instantiated per SKILL.md at runtime, not registered statically.
        ->reject(fn (string $class): bool => $class === 'DynamicSkillTool')
        ->sort()
        ->values();

    $registered = collect(ToolRegistry::tools())
        ->map(fn (string $tool): string => class_basename($tool))
        ->sort()
        ->values();

    expect($registered->all())->toBe($shipped->all());
});

it('returns an array of class strings', function () {
    $tools = ToolRegistry::tools();

    foreach ($tools as $tool) {
        expect($tool)->toBeString();
        expect(class_exists($tool))->toBeTrue();
    }
});

it('does not register the same tool twice', function () {
    $tools = ToolRegistry::tools();

    expect(array_unique($tools))->toHaveCount(count($tools));
});

it('contains CatalogSchemaTool as the first entry', function () {
    $tools = ToolRegistry::tools();

    expect($tools[0])->toBe(CatalogSchemaTool::class);
});
