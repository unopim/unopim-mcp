<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Models\Tag;
use Webkul\DAM\Services\DirectoryPermissionService;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Dam\AssetGetTool;
use Webkul\MCP\Tools\Dam\AssetSearchTool;
use Webkul\MCP\Tools\Dam\DirectoryTreeTool;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    if (! class_exists(Asset::class)) {
        $this->markTestSkipped('unopim/dam is not installed in this app.');
    }
});

/**
 * Sign in an admin whose role has the given permissions and, for a custom role, is
 * limited to the given DAM directories.
 *
 * @param  list<string>  $permissions
 * @param  list<int>|null  $directoryIds
 */
function actingAsDamAdmin(string $type = 'all', array $permissions = [], ?array $directoryIds = null): void
{
    $role = Role::factory()->create(['permission_type' => $type, 'permissions' => $permissions]);

    foreach ($directoryIds ?? [] as $directoryId) {
        DB::table('dam_directory_role')->insert(['directory_id' => $directoryId, 'role_id' => $role->id]);
    }

    app(DirectoryPermissionService::class)->flush();

    test()->actingAs(Admin::factory()->create(['role_id' => $role->id]), 'admin');
}

/**
 * Create an asset inside the given directory, with the given tags.
 *
 * @param  array<string, mixed>  $attributes
 * @param  list<string>  $tags
 */
function damAsset(Directory $directory, array $attributes = [], array $tags = []): Asset
{
    $asset = Asset::factory()->create($attributes);

    $asset->directories()->attach($directory->id);

    foreach ($tags as $name) {
        $asset->tags()->attach(Tag::firstOrCreate(['name' => $name])->id);
    }

    return $asset;
}

/**
 * The ids of the assets in a search_assets payload.
 *
 * @param  array<string, mixed>  $payload
 * @return list<int>
 */
function damAssetIds(array $payload): array
{
    return collect($payload['assets'] ?? [])->pluck('id')->sort()->values()->all();
}

it('filters assets on their columns, including IN lists and numeric comparisons', function () {
    $directory = Directory::factory()->create(['name' => 'mcp-filters']);

    $png = damAsset($directory, ['file_name' => 'mcp-hero.png', 'extension' => 'png', 'file_size' => 5000]);
    $jpg = damAsset($directory, ['file_name' => 'mcp-hero.jpg', 'extension' => 'jpg', 'file_size' => 900]);
    damAsset($directory, ['file_name' => 'mcp-hero.pdf', 'extension' => 'pdf', 'file_size' => 7000]);

    $byName = $this->toolPayload(AssetSearchTool::class, [
        'directory_id' => $directory->id,
        'filters' => [
            ['field' => 'file_name', 'operator' => 'CONTAINS', 'value' => 'hero'],
            ['field' => 'extension', 'operator' => 'IN', 'value' => ['png', 'jpg']],
        ],
    ]);

    $bySize = $this->toolPayload(AssetSearchTool::class, [
        'directory_id' => $directory->id,
        'filters' => [
            ['field' => 'extension', 'operator' => 'IN', 'value' => 'png,jpg'],
            ['field' => 'file_size', 'operator' => '>', 'value' => 1000],
        ],
    ]);

    expect(damAssetIds($byName))->toBe(collect([$png->id, $jpg->id])->sort()->values()->all())
        ->and(damAssetIds($bySize))->toBe([$png->id]);
});

it('rejects a filter on a column assets do not have', function () {
    UnoPimAgentServer::tool(AssetSearchTool::class, [
        'filters' => [['field' => 'no_such_column', 'operator' => '=', 'value' => 'x']],
    ])->assertHasErrors();
});

it('narrows assets to a tag', function () {
    $directory = Directory::factory()->create(['name' => 'mcp-tags']);

    $summer = damAsset($directory, [], ['mcp-summer-2026']);
    damAsset($directory, [], ['mcp-winter-2026']);

    $payload = $this->toolPayload(AssetSearchTool::class, ['tag' => 'summer', 'directory_id' => $directory->id]);

    expect(damAssetIds($payload))->toBe([$summer->id]);
});

it('matches LIKE wildcards in a tag literally', function () {
    $directory = Directory::factory()->create(['name' => 'mcp-wildcards']);

    $underscore = damAsset($directory, [], ['mcp_a_b']);
    damAsset($directory, [], ['mcpxaxb']);
    damAsset($directory, [], ['mcp-plain']);

    $byUnderscore = $this->toolPayload(AssetSearchTool::class, ['tag' => 'mcp_a_b', 'directory_id' => $directory->id]);
    $byPercent = $this->toolPayload(AssetSearchTool::class, ['tag' => '%', 'directory_id' => $directory->id]);

    expect(damAssetIds($byUnderscore))->toBe([$underscore->id])
        ->and(damAssetIds($byPercent))->toBe([]);
});

it('narrows assets to a directory', function () {
    $inside = Directory::factory()->create(['name' => 'mcp-inside']);
    $outside = Directory::factory()->create(['name' => 'mcp-outside']);

    $asset = damAsset($inside);
    damAsset($outside);

    $payload = $this->toolPayload(AssetSearchTool::class, ['directory_id' => $inside->id]);

    expect(damAssetIds($payload))->toBe([$asset->id]);
});

it('only searches the directories a restricted role may open', function () {
    $granted = Directory::factory()->create(['name' => 'mcp-granted']);
    $hidden = Directory::factory()->create(['name' => 'mcp-hidden']);

    $visible = damAsset($granted, ['file_name' => 'mcp-restricted-a.jpg']);
    damAsset($hidden, ['file_name' => 'mcp-restricted-b.jpg']);

    actingAsDamAdmin('custom', ['dam', 'dam.asset'], [$granted->id]);

    $payload = $this->toolPayload(AssetSearchTool::class, [
        'filters' => [['field' => 'file_name', 'operator' => 'STARTS WITH', 'value' => 'mcp-restricted-']],
    ]);

    expect(damAssetIds($payload))->toBe([$visible->id]);
});

it('gets an asset with its tags and directories', function () {
    $directory = Directory::factory()->create(['name' => 'mcp-get']);

    $asset = damAsset($directory, ['file_name' => 'mcp-get.jpg'], ['mcp-get-tag']);

    $payload = $this->toolPayload(AssetGetTool::class, ['id' => $asset->id]);

    expect($payload['id'])->toBe($asset->id)
        ->and($payload['file_name'])->toBe('mcp-get.jpg')
        ->and($payload['tags'])->toBe(['mcp-get-tag'])
        ->and($payload['directories'])->toBe([['id' => $directory->id, 'name' => 'mcp-get']])
        ->and($payload)->not->toHaveKey('download_url');
});

it('reports a missing asset with a translated message', function () {
    app()->setLocale('fr_FR');

    UnoPimAgentServer::tool(AssetGetTool::class, ['id' => 2147483647])
        ->assertHasErrors([trans('mcp::app.tools.get-asset.not-found', ['id' => 2147483647])]);

    expect(trans('mcp::app.tools.get-asset.not-found', ['id' => 5]))->toBe('La ressource [5] est introuvable.');
});

it('reports an asset outside the directories a restricted role may open as not found', function () {
    $granted = Directory::factory()->create(['name' => 'mcp-get-granted']);
    $hidden = Directory::factory()->create(['name' => 'mcp-get-hidden']);

    $asset = damAsset($hidden);

    actingAsDamAdmin('custom', ['dam', 'dam.asset', 'dam.asset.view'], [$granted->id]);

    UnoPimAgentServer::tool(AssetGetTool::class, ['id' => $asset->id])
        ->assertHasErrors([trans('mcp::app.tools.get-asset.not-found', ['id' => $asset->id])]);
});

it('rejects an asset id outside the integer column range before querying', function () {
    UnoPimAgentServer::tool(AssetGetTool::class, ['id' => PHP_INT_MAX])
        ->assertHasErrors([trans('validation.max.numeric', ['attribute' => 'id', 'max' => 2147483647])]);
});

it('advertises the id bounds in the schemas', function (string $tool, string $property) {
    $schema = app($tool)->toArray()['inputSchema']['properties'][$property];

    expect($schema['minimum'] ?? null)->toBe(1)
        ->and($schema['maximum'] ?? null)->toBe(2147483647);
})->with([
    [AssetGetTool::class, 'id'],
    [AssetSearchTool::class, 'directory_id'],
    [DirectoryTreeTool::class, 'parent_id'],
]);

it('returns a download url only to a caller with the dam download permission', function () {
    Storage::fake(Directory::getAssetDisk());

    $directory = Directory::factory()->create(['name' => 'mcp-download']);
    $asset = damAsset($directory, ['path' => 'assets/mcp-download/a.jpg']);

    Storage::disk(Directory::getAssetDisk())->put($asset->path, 'jpg');

    actingAsDamAdmin('custom', ['dam', 'dam.asset', 'dam.asset.view'], [$directory->id]);

    $withoutPermission = $this->toolPayload(AssetGetTool::class, ['id' => $asset->id, 'include_download_url' => true]);

    actingAsDamAdmin('custom', ['dam', 'dam.asset', 'dam.asset.view', 'dam.asset.download'], [$directory->id]);

    $withPermission = $this->toolPayload(AssetGetTool::class, ['id' => $asset->id, 'include_download_url' => true]);

    expect($withoutPermission)->not->toHaveKey('download_url')
        ->and($withPermission['download_url'])->toBeString()->not->toBeEmpty()
        ->and($withPermission['download_url_expires_in'])->toBe('10 minutes');
});

it('returns a null download url when the file is missing from the asset disk', function () {
    Storage::fake(Directory::getAssetDisk());

    $asset = damAsset(Directory::factory()->create(), ['path' => 'assets/mcp-missing/a.jpg']);

    actingAsDamAdmin();

    $payload = $this->toolPayload(AssetGetTool::class, ['id' => $asset->id, 'include_download_url' => true]);

    expect($payload)->toHaveKey('download_url')
        ->and($payload['download_url'])->toBeNull();
});

it('builds a valid attachment header for a file name with quotes and non-ASCII characters', function () {
    $method = new ReflectionMethod(AssetGetTool::class, 'contentDisposition');

    $header = $method->invoke(app(AssetGetTool::class), 'Zoë "summer"/2026.jpg');

    expect($header)->toStartWith('attachment; filename="Zoe \"summer\"-2026.jpg"')
        ->and($header)->toContain("filename*=utf-8''Zo%C3%AB%20%22summer%22-2026.jpg");
});

it('browses the directory tree to the requested depth', function () {
    $root = Directory::factory()->create(['name' => 'mcp-root']);
    $child = Directory::factory()->create(['name' => 'mcp-child', 'parent_id' => $root->id]);
    $grandchild = Directory::factory()->create(['name' => 'mcp-grandchild', 'parent_id' => $child->id]);

    damAsset($child);
    damAsset($child);

    $tree = fn (int $depth): array => $this->toolPayload(DirectoryTreeTool::class, ['parent_id' => $root->id, 'depth' => $depth])['directories'];

    expect($tree(1))->toBe([['id' => $child->id, 'name' => 'mcp-child', 'asset_count' => 2]])
        ->and($tree(2))->toBe([[
            'id' => $child->id,
            'name' => 'mcp-child',
            'asset_count' => 2,
            'children' => [['id' => $grandchild->id, 'name' => 'mcp-grandchild', 'asset_count' => 0]],
        ]])
        ->and($tree(3)[0]['children'][0]['children'])->toBe([]);
});

it('starts the directory tree at the root when no parent is given', function () {
    $root = Directory::factory()->create(['name' => 'mcp-top-level']);

    $directories = $this->toolPayload(DirectoryTreeTool::class, ['depth' => 1])['directories'];

    expect(collect($directories)->pluck('id'))->toContain($root->id)
        ->and(collect($directories)->pluck('children')->filter())->toBeEmpty();
});

it('loads the directory tree with one query per level', function () {
    $root = Directory::factory()->create(['name' => 'mcp-wide']);

    foreach (range(1, 4) as $index) {
        $child = Directory::factory()->create(['name' => "mcp-wide-{$index}", 'parent_id' => $root->id]);

        Directory::factory()->count(2)->create(['parent_id' => $child->id]);
    }

    $directoryTable = DB::getTablePrefix().'dam_directories';
    $queries = 0;

    DB::listen(function ($query) use (&$queries, $directoryTable) {
        if (str_contains($query->sql, $directoryTable)) {
            $queries++;
        }
    });

    $payload = $this->toolPayload(DirectoryTreeTool::class, ['parent_id' => $root->id, 'depth' => 3]);

    expect($payload['directories'])->toHaveCount(4)
        ->and($queries)->toBeLessThanOrEqual(4);
});

it('reports a missing parent directory with a translated message', function () {
    UnoPimAgentServer::tool(DirectoryTreeTool::class, ['parent_id' => 2147483647])
        ->assertHasErrors([trans('mcp::app.tools.browse-asset-directories.not-found', ['id' => 2147483647])]);
});

it('only shows a restricted role its directories and the way to them', function () {
    $root = Directory::factory()->create(['name' => 'mcp-restricted-root']);
    $granted = Directory::factory()->create(['name' => 'mcp-restricted-granted', 'parent_id' => $root->id]);
    $hidden = Directory::factory()->create(['name' => 'mcp-restricted-hidden', 'parent_id' => $root->id]);

    damAsset($root);
    damAsset($granted);

    actingAsDamAdmin('custom', ['dam', 'dam.directory'], [$granted->id]);

    $directories = $this->toolPayload(DirectoryTreeTool::class, ['depth' => 2])['directories'];

    $node = collect($directories)->firstWhere('id', $root->id);

    expect($node['asset_count'])->toBe(0)
        ->and($node['children'])->toBe([['id' => $granted->id, 'name' => 'mcp-restricted-granted', 'asset_count' => 1]]);

    UnoPimAgentServer::tool(DirectoryTreeTool::class, ['parent_id' => $hidden->id])
        ->assertHasErrors([trans('mcp::app.tools.browse-asset-directories.not-found', ['id' => $hidden->id])]);
});
