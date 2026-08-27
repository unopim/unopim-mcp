<?php

namespace Webkul\MCP\Tools\Dam;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\MCP\Tools\BaseMcpTool;

class AssetGetTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Get one DAM asset with its metadata, tags, custom properties, directories and the products or categories it is linked to. Read-only.';

    /**
     * The tool's name.
     */
    public string $name = 'get_asset';

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'id'                   => ['required', 'integer'],
            'include_download_url' => ['nullable', 'boolean'],
        ]);

        $asset = Asset::with(['tags', 'properties', 'directories', 'resources'])
            ->find($validated['id']);

        if (! $asset) {
            return Response::error("Asset [{$validated['id']}] not found.");
        }

        return Response::json([
            'id'          => $asset->id,
            'file_name'   => $asset->file_name,
            'file_type'   => $asset->file_type,
            'mime_type'   => $asset->mime_type,
            'extension'   => $asset->extension,
            'file_size'   => $asset->file_size,
            'path'        => $asset->path,
            'meta_data'   => $asset->meta_data,
            'tags'        => $asset->tags->pluck('name')->values()->all(),
            'properties'  => $asset->properties
                ->map(fn ($property) => [
                    'name'  => $property->name,
                    'value' => $property->value,
                ])->values()->all(),
            'directories' => $asset->directories
                ->map(fn ($directory) => [
                    'id'   => $directory->id,
                    'name' => $directory->name,
                ])->values()->all(),
            'linked_resources' => $asset->resources
                ->map(fn ($mapping) => [
                    'type'          => $mapping->type,
                    'related_field' => $mapping->related_field,
                    'product_id'    => $mapping->product_id,
                    'category_id'   => $mapping->category_id,
                ])->values()->all(),
            'created_at' => (string) $asset->created_at,
            'updated_at' => (string) $asset->updated_at,
        ] + ($validated['include_download_url'] ?? false ? [
            'download_url'            => $this->downloadUrl($asset),
            'download_url_expires_in' => '10 minutes',
        ] : []));
    }

    /**
     * A time-limited download URL, the same way the DAM's own REST endpoint
     * builds one: a presigned object URL on S3, a signed application route on
     * the local disk. Expires after ten minutes, so it is for immediate use,
     * not for storing in another system.
     */
    protected function downloadUrl(Asset $asset): ?string
    {
        $disk = Directory::getAssetDisk();

        if (! Storage::disk($disk)->exists($asset->path)) {
            return null;
        }

        if (config('filesystems.default') === 's3') {
            return Storage::disk($disk)->temporaryUrl(
                $asset->path,
                now()->addMinutes(10),
                ['ResponseContentDisposition' => 'attachment; filename="'.$asset->file_name.'"']
            );
        }

        return URL::temporarySignedRoute(
            'admin.api.dam.assets.private.download',
            now()->addMinutes(10),
            ['id' => $asset->id]
        );
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The asset id (see search_assets).')
                ->required(),
            'include_download_url' => $schema->boolean()
                ->description('Also return a time-limited download URL for the file (expires after 10 minutes).')
                ->default(false),
        ];
    }
}
