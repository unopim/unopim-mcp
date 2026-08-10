<?php

namespace Webkul\MCP\Tools\Dam;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\DAM\Models\Directory;
use Webkul\MCP\Tools\BaseMcpTool;

class DirectoryTreeTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Browse the DAM directory tree, with the number of assets per directory. Read-only.';

    /**
     * The tool's name.
     */
    public string $name = 'browse_asset_directories';

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer'],
            'depth'     => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $depth = (int) ($validated['depth'] ?? 2);

        $rootQuery = Directory::withCount('assets');

        if (! empty($validated['parent_id'])) {
            $rootQuery->where('parent_id', (int) $validated['parent_id']);
        } else {
            $rootQuery->whereNull('parent_id');
        }

        $directories = $rootQuery->orderBy('name')->get();

        return Response::json([
            'directories' => $directories
                ->map(fn ($directory) => $this->node($directory, $depth - 1))
                ->values()
                ->all(),
        ]);
    }

    /**
     * One directory with its children up to the remaining depth.
     *
     * @return array<string, mixed>
     */
    protected function node(Directory $directory, int $remainingDepth): array
    {
        $node = [
            'id'          => $directory->id,
            'name'        => $directory->name,
            'asset_count' => (int) $directory->assets_count,
        ];

        if ($remainingDepth > 0) {
            $node['children'] = Directory::withCount('assets')
                ->where('parent_id', $directory->id)
                ->orderBy('name')
                ->get()
                ->map(fn ($child) => $this->node($child, $remainingDepth - 1))
                ->values()
                ->all();
        }

        return $node;
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'parent_id' => $schema->integer()
                ->description('Directory to start from. Omit to start at the root.'),
            'depth' => $schema->integer()
                ->description('How many levels of children to include (1-5).')
                ->default(2),
        ];
    }
}
