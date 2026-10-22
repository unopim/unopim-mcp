<?php

namespace Webkul\MCP\Tools\Dam;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\MCP\Services\DamDirectoryAccess;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class AssetGetTool extends BaseMcpTool
{
    /**
     * The DAM's signed download route for assets on a disk that cannot sign URLs itself.
     */
    public const SIGNED_DOWNLOAD_ROUTE = 'admin.api.dam.assets.private.download';

    /**
     * The tool's description.
     */
    protected string $description = 'Get one DAM asset with its metadata, tags, custom properties, directories and the products or categories it is linked to. download_url is omitted when the caller lacks the DAM download permission. Read-only.';

    /**
     * The tool's title.
     */
    protected string $title = 'Get asset';

    /**
     * The tool's name.
     */
    public string $name = 'get_asset';

    public function __construct(
        protected DamDirectoryAccess $directoryAccess
    ) {}

    /**
     * An asset outside the directories the caller may open is reported as not found, so
     * the tool does not reveal which ids exist. An asset in any accessible directory is
     * returned, as in the DAM's asset grid, and only its accessible directories are listed.
     */
    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'include_download_url' => ['nullable', 'boolean'],
        ]);

        $accessible = $this->directoryAccess->accessibleIds();

        $inAccessibleDirectories = fn ($directories) => $accessible === null
            ? $directories
            : $directories->whereIn('dam_directories.id', $accessible);

        $query = Asset::with(['tags', 'properties', 'directories' => $inAccessibleDirectories, 'resources']);

        if ($accessible !== null) {
            $query->whereHas('directories', $inAccessibleDirectories);
        }

        $asset = $query->find($validated['id']);

        if (! $asset) {
            return Response::error(trans('mcp::app.tools.get-asset.not-found', ['id' => $validated['id']]));
        }

        $download = ($validated['include_download_url'] ?? false) && $this->allows('dam.asset.download')
            ? ['download_url' => $this->downloadUrl($asset), 'download_url_expires_in' => '10 minutes']
            : [];

        return Response::json([
            'id' => $asset->id,
            'file_name' => $asset->file_name,
            'file_type' => $asset->file_type,
            'mime_type' => $asset->mime_type,
            'extension' => $asset->extension,
            'file_size' => $asset->file_size,
            'path' => $asset->path,
            'meta_data' => $asset->meta_data,
            'tags' => $asset->tags->pluck('name')->values()->all(),
            'properties' => $asset->properties
                ->map(fn ($property): array => [
                    'name' => $property->name,
                    'value' => $property->value,
                ])->values()->all(),
            'directories' => $asset->directories
                ->map(fn ($directory): array => [
                    'id' => $directory->id,
                    'name' => $directory->name,
                ])->values()->all(),
            'linked_resources' => $asset->resources
                ->map(fn ($mapping): array => [
                    'type' => $mapping->type,
                    'related_field' => $mapping->related_field,
                    'product_id' => $mapping->product_id,
                    'category_id' => $mapping->category_id,
                ])->values()->all(),
            'created_at' => (string) $asset->created_at,
            'updated_at' => (string) $asset->updated_at,
            ...$download,
        ]);
    }

    /**
     * A download URL that expires after ten minutes, for immediate use rather than for
     * storing in another system.
     *
     * The asset's own disk signs the URL when it can (a presigned object URL on S3, a
     * signed serve URL on a local disk that serves files). Otherwise the DAM's signed
     * download route is used, when the installed DAM version registers it. Null when
     * the file is missing or no signed URL can be built.
     */
    protected function downloadUrl(Asset $asset): ?string
    {
        $storage = Storage::disk(Directory::getAssetDisk());

        if (! $storage->exists($asset->path)) {
            return null;
        }

        if ($storage->providesTemporaryUrls()) {
            return $storage->temporaryUrl($asset->path, now()->addMinutes(10), [
                'ResponseContentDisposition' => $this->contentDisposition($asset->file_name ?: basename($asset->path)),
            ]);
        }

        if (! Route::has(self::SIGNED_DOWNLOAD_ROUTE)) {
            return null;
        }

        return URL::temporarySignedRoute(self::SIGNED_DOWNLOAD_ROUTE, now()->addMinutes(10), ['id' => $asset->id]);
    }

    /**
     * An attachment Content-Disposition for the file name, quoted and with an ASCII fallback
     * so quotes, slashes or non-ASCII characters in the name cannot break the header.
     */
    protected function contentDisposition(string $fileName): string
    {
        $fileName = str_replace(['/', '\\'], '-', $fileName);

        $fallback = preg_replace('/[^\x20-\x7e]|%/', '', Str::ascii($fileName));

        return HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName, $fallback !== '' ? $fallback : 'download');
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The asset id (see search_assets).')
                ->min(1)
                ->max(2147483647)
                ->required(),
            'include_download_url' => $schema->boolean()
                ->description('Also return a download URL for the file that expires after 10 minutes. Requires the DAM download permission.')
                ->default(false),
        ];
    }
}
