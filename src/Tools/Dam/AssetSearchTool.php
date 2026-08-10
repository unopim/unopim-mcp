<?php

namespace Webkul\MCP\Tools\Dam;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\DAM\Models\Asset;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;

class AssetSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search digital assets in the DAM by name, type, tag or directory, with generic filters and pagination. Read-only.';

    /**
     * The tool's name.
     */
    public string $name = 'search_assets';

    public function __construct(
        protected UnoPimQueryBuilder $queryBuilder
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'filters'      => ['nullable', 'array'],
            'tag'          => ['nullable', 'string', 'max:255'],
            'directory_id' => ['nullable', 'integer'],
            'limit'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'       => ['nullable', 'string'],
        ]);

        $query = Asset::query();

        if (! empty($validated['filters'])) {
            $this->queryBuilder->applyFilters($query, $validated['filters']);
        }

        if (! empty($validated['tag'])) {
            $tag = $validated['tag'];
            $query->whereHas('tags', fn ($q) => $q->where('name', 'like', '%'.$tag.'%'));
        }

        if (! empty($validated['directory_id'])) {
            $directoryId = (int) $validated['directory_id'];
            $query->whereHas('directories', fn ($q) => $q->where('directory_id', $directoryId));
        }

        $paginator = $this->queryBuilder->paginate(
            $query->orderByDesc('id'),
            (int) ($validated['limit'] ?? 25),
            $validated['cursor'] ?? null
        );

        return Response::json([
            'count'       => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more'    => $paginator->hasMorePages(),
            'assets'      => $paginator->map(fn ($asset) => [
                'id'        => $asset->id,
                'file_name' => $asset->file_name,
                'file_type' => $asset->file_type,
                'mime_type' => $asset->mime_type,
                'file_size' => $asset->file_size,
                'path'      => $asset->path,
            ])->values()->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filters' => $schema->array()
                ->description('List of filters over asset columns (file_name, file_type, extension, mime_type): [{field, operator, value}]. For IN and NOT IN, pass a comma-separated list ("a,b") or an array.')
                ->items(
                    $schema->object([
                        'field'    => $schema->string()->description('The field to filter by.'),
                        'operator' => $schema->string()->description('The comparison operator.'),
                        'value'    => $schema->string()->description('The value to compare against.'),
                    ])
                ),
            'tag' => $schema->string()
                ->description('Only assets carrying a tag whose name contains this text.'),
            'directory_id' => $schema->integer()
                ->description('Only assets inside this directory (see browse_asset_directories).'),
            'limit' => $schema->integer()
                ->description('Number of results per page.')
                ->default(25),
            'cursor' => $schema->string()
                ->description('Pagination cursor.'),
        ];
    }
}
