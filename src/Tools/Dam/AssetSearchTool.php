<?php

namespace Webkul\MCP\Tools\Dam;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\DAM\Models\Asset;
use Webkul\MCP\Services\DamDirectoryAccess;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class AssetSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search digital assets in the DAM by name, type, tag or directory, with generic filters and pagination. Only assets in directories the caller may open are returned. Read-only.';

    /**
     * The tool's title.
     */
    protected string $title = 'Search assets';

    /**
     * The tool's name.
     */
    public string $name = 'search_assets';

    public function __construct(
        protected UnoPimQueryBuilder $queryBuilder,
        protected DamDirectoryAccess $directoryAccess
    ) {}

    /**
     * The tag filter matches tag names containing the given text literally, so `%` and `_`
     * are escaped rather than acting as LIKE wildcards. For a role the DAM limits to some
     * directories, `directory_id` only matches when that directory is accessible, so an
     * asset that is also in a hidden directory cannot be found through it.
     */
    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'filters' => ['nullable', 'array'],
            'tag' => ['nullable', 'string', 'max:255'],
            'directory_id' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = Asset::query();

        if (! empty($validated['filters'])) {
            $this->queryBuilder->applyFilters($query, $validated['filters']);
        }

        if (isset($validated['tag']) && $validated['tag'] !== '') {
            $tag = '%'.addcslashes($validated['tag'], '\\%_').'%';

            $query->whereHas('tags', fn ($tags) => $tags->where('name', 'like', $tag));
        }

        $directoryId = empty($validated['directory_id']) ? null : (int) $validated['directory_id'];
        $accessible = $this->directoryAccess->accessibleIds();

        if ($directoryId !== null || $accessible !== null) {
            $query->whereHas('directories', fn ($directories) => $directories
                ->when($directoryId !== null, fn ($directories) => $directories->where('dam_directories.id', $directoryId))
                ->when($accessible !== null, fn ($directories) => $directories->whereIn('dam_directories.id', $accessible)));
        }

        $paginator = $this->queryBuilder->paginate(
            $query->orderByDesc('id'),
            (int) ($validated['limit'] ?? 25),
            $validated['cursor'] ?? null
        );

        return Response::json([
            'count' => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'assets' => $paginator->map(fn ($asset): array => [
                'id' => $asset->id,
                'file_name' => $asset->file_name,
                'file_type' => $asset->file_type,
                'mime_type' => $asset->mime_type,
                'file_size' => $asset->file_size,
                'path' => $asset->path,
            ])->values()->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filters' => $this->filtersSchema(
                $schema,
                'List of filters: [{field, operator, value}].',
                'An asset column (id, file_name, file_type, extension, mime_type, file_size, path, created_at, updated_at).',
            ),
            'tag' => $schema->string()
                ->description('Only assets carrying a tag whose name contains this text.'),
            'directory_id' => $schema->integer()
                ->description('Only assets inside this directory (see browse_asset_directories).')
                ->min(1)
                ->max(2147483647),
            'limit' => $schema->integer()
                ->description('Number of results per page.')
                ->default(25),
            'cursor' => $schema->string()
                ->description('Pagination cursor.'),
        ];
    }
}
