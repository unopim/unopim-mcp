<?php

use Webkul\MCP\Registry\ToolRegistry;
use Webkul\MCP\Tools\Catalog\CategoryUpsertTool;
use Webkul\MCP\Tools\Catalog\ProductSearchTool;
use Webkul\MCP\Tools\Catalog\ProductUpsertTool;
use Webkul\MCP\Tools\Dev\DatabaseQueryTool;
use Webkul\MCP\Tools\Dev\DynamicSkillTool;
use Webkul\MCP\Tools\Dev\RunSkillTool;

/**
 * `destructiveHint` defaults to true and `readOnlyHint` to false, so a catalog
 * of unannotated tools tells the client every call might overwrite data. The
 * hints are what let a client confirm before a write and skip confirming for a
 * search.
 */
it('marks read-only tools so clients need not confirm them', function (string $tool) {
    expect(app($tool)->toArray()['annotations'])->toMatchArray(['readOnlyHint' => true]);
})->with([ProductSearchTool::class, DatabaseQueryTool::class]);

it('marks catalog writes as destructive', function (string $tool) {
    expect(app($tool)->toArray()['annotations'])->toMatchArray(['destructiveHint' => true]);
})->with([ProductUpsertTool::class, CategoryUpsertTool::class]);

it('marks an upsert idempotent because re-sending the same payload settles the same state', function () {
    expect(app(ProductUpsertTool::class)->toArray()['annotations'])
        ->toMatchArray(['idempotentHint' => true]);
});

it('marks skill execution as open world since it reaches beyond the catalog', function () {
    expect(app(RunSkillTool::class)->toArray()['annotations'])
        ->toMatchArray(['openWorldHint' => true, 'destructiveHint' => true]);
});

it('never advertises a tool as both read-only and destructive', function () {
    foreach (ToolRegistry::tools() as $tool) {
        $annotations = app($tool)->toArray()['annotations'];

        expect(($annotations['readOnlyHint'] ?? false) && ($annotations['destructiveHint'] ?? false))
            ->toBeFalse("{$tool} claims to be both read-only and destructive");
    }
});

it('annotates every registered tool', function () {
    $unannotated = collect(ToolRegistry::tools())
        ->reject(fn (string $tool): bool => (array) app($tool)->toArray()['annotations'] !== [])
        ->values()
        ->all();

    expect($unannotated)->toBeEmpty();
});

/**
 * Without an explicit title laravel/mcp falls back to the class name, so
 * clients list tools as "Product Get Tool" instead of a readable action.
 */
it('gives every registered tool a human readable title', function () {
    $untitled = collect(ToolRegistry::tools())
        ->filter(fn (string $tool): bool => str_ends_with(app($tool)->toArray()['title'], ' Tool'))
        ->values()
        ->all();

    expect($untitled)->toBeEmpty();
});

it('titles a dynamic skill after the skill it runs', function () {
    $tool = new DynamicSkillTool(['name' => 'catalog-audit', 'description' => 'Audit the catalog.']);

    expect($tool->toArray()['title'])->toBe('Run Catalog Audit skill');
});
