<?php

namespace Webkul\MCP\Tools\Dam;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\DAM\Models\Directory;
use Webkul\MCP\Services\DamDirectoryAccess;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class DirectoryTreeTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Browse the DAM directory tree, with the number of assets per directory. Only directories the caller may see are returned. Read-only.';

    /**
     * The tool's title.
     */
    protected string $title = 'Browse asset directories';

    /**
     * The tool's name.
     */
    public string $name = 'browse_asset_directories';

    public function __construct(
        protected DamDirectoryAccess $directoryAccess
    ) {}

    /**
     * The tree is loaded one level per query, so a call costs at most `depth` queries
     * however wide the tree is.
     */
    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'depth' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $depth = (int) ($validated['depth'] ?? 2);
        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        $viewable = $this->directoryAccess->viewableIds();

        if ($parentId !== null && ! $this->directoryQuery($viewable)->whereKey($parentId)->exists()) {
            return Response::error(trans('mcp::app.tools.browse-asset-directories.not-found', ['id' => $parentId]));
        }

        $levels = [];
        $parentIds = $parentId === null ? null : [$parentId];

        for ($level = 0; $level < $depth; $level++) {
            $query = $this->directoryQuery($viewable)->withCount('assets')->orderBy('name');

            $directories = $parentIds === null
                ? $query->whereNull('parent_id')->get()
                : $query->whereIn('parent_id', $parentIds)->get();

            if ($directories->isEmpty()) {
                break;
            }

            $levels[] = $directories->groupBy(fn (Directory $directory): int => (int) $directory->parent_id);
            $parentIds = $directories->modelKeys();
        }

        return Response::json([
            'directories' => $this->nodes($levels, 0, $parentId, $depth, $this->directoryAccess->accessibleIds()),
        ]);
    }

    /**
     * The directories the caller may see, all of them when unrestricted.
     *
     * @param  list<int>|null  $viewable
     */
    protected function directoryQuery(?array $viewable): Builder
    {
        $query = Directory::query();

        return $viewable === null ? $query : $query->whereIn('id', $viewable);
    }

    /**
     * The nodes of one level under the given parent, each with its children up to the
     * requested depth. Each level is grouped by parent id, with 0 for the root. A directory
     * the caller may see only as the way to an accessible one reports no assets.
     *
     * @param  list<Collection<int, Collection<int, Directory>>>  $levels
     * @param  list<int>|null  $accessible
     * @return list<array<string, mixed>>
     */
    protected function nodes(array $levels, int $level, ?int $parentId, int $depth, ?array $accessible): array
    {
        return ($levels[$level][$parentId ?? 0] ?? collect())
            ->map(fn (Directory $directory): array => [
                'id' => $directory->id,
                'name' => $directory->name,
                'asset_count' => $accessible === null || in_array($directory->id, $accessible, true)
                    ? (int) $directory->assets_count
                    : 0,
                ...($level + 1 < $depth
                    ? ['children' => $this->nodes($levels, $level + 1, $directory->id, $depth, $accessible)]
                    : []),
            ])
            ->values()
            ->all();
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'parent_id' => $schema->integer()
                ->description('Directory to start from. Omit to start at the root.')
                ->min(1)
                ->max(2147483647),
            'depth' => $schema->integer()
                ->description('How many levels of directories to include (1-5).')
                ->min(1)
                ->max(5)
                ->default(2),
        ];
    }
}
