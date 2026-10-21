<?php

use Webkul\DAM\Models\Asset;
use Webkul\MCP\Registry\ToolRegistry;
use Webkul\MCP\Tools\Dam\AssetGetTool;
use Webkul\MCP\Tools\Dam\AssetSearchTool;
use Webkul\MCP\Tools\Dam\DirectoryTreeTool;

it('registers the dam tools when the dam package is installed', function () {
    if (! class_exists(Asset::class)) {
        $this->markTestSkipped('unopim/dam is not installed in this app.');
    }

    $tools = ToolRegistry::tools();

    expect($tools)->toContain(AssetSearchTool::class)
        ->and($tools)->toContain(AssetGetTool::class)
        ->and($tools)->toContain(DirectoryTreeTool::class);
});

it('keeps the dam tools read-only: no write tool classes exist in the group', function () {
    $shipped = collect(glob(dirname(__DIR__, 2).'/src/Tools/Dam/*.php'))
        ->map(fn (string $path): string => basename($path, '.php'));

    expect($shipped->all())->toBe(['AssetGetTool', 'AssetSearchTool', 'DirectoryTreeTool']);
});

it('leaves the dam tools out when the dam package is absent', function () {
    if (class_exists(Asset::class)) {
        $this->markTestSkipped('unopim/dam is installed in this app.');
    }

    expect(ToolRegistry::tools())->not->toContain(AssetSearchTool::class)
        ->and(ToolRegistry::tools())->not->toContain(AssetGetTool::class)
        ->and(ToolRegistry::tools())->not->toContain(DirectoryTreeTool::class);
});

it('marks every dam tool read-only with a readable title', function (string $tool, string $title) {
    $definition = app($tool)->toArray();

    expect($definition['annotations'])->toMatchArray(['readOnlyHint' => true])
        ->and($definition['title'])->toBe($title);
})->with([
    [AssetSearchTool::class, 'Search assets'],
    [AssetGetTool::class, 'Get asset'],
    [DirectoryTreeTool::class, 'Browse asset directories'],
]);
