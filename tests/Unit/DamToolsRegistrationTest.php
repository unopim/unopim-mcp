<?php

use Webkul\MCP\Registry\ToolRegistry;
use Webkul\MCP\Tools\Dam\AssetGetTool;
use Webkul\MCP\Tools\Dam\AssetSearchTool;
use Webkul\MCP\Tools\Dam\DirectoryTreeTool;

it('registers the dam tools when the dam package is installed', function () {
    if (! class_exists(\Webkul\DAM\Models\Asset::class)) {
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
