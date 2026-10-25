<?php

namespace Webkul\MCP\Services;

use Illuminate\Database\Eloquent\Builder;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Directory;
use Webkul\DAM\Services\DirectoryPermissionService;

/**
 * The MCP package's single access point to the optional unopim/dam package: its asset and
 * directory models, its asset disk and the directories the current caller may open.
 *
 * The MCP package does not depend on the DAM, so this is the only class that references
 * DAM classes. Static analysis without the DAM cannot resolve them, which is why the lines
 * that use them ignore `class.notFound` and nothing else; with the DAM installed the same
 * lines are analysed against the real classes. The DAM tools work on the Eloquent builders
 * and models returned here.
 *
 * A custom role can be limited to a set of directories in the DAM. The DAM's admin
 * screens and REST API apply that limit through DirectoryPermissionService; the MCP
 * DAM tools apply it the same way so an MCP client never sees more than the DAM would
 * show the same user. DAM versions without the service have no directory limits.
 */
class DamAccess
{
    /**
     * Whether the unopim/dam package is installed.
     */
    public static function installed(): bool
    {
        return class_exists(Asset::class);
    }

    /**
     * A query on the DAM's assets.
     */
    public function assets(): Builder
    {
        return Asset::query(); // @phpstan-ignore class.notFound
    }

    /**
     * A query on the DAM's directories.
     */
    public function directories(): Builder
    {
        return Directory::query(); // @phpstan-ignore class.notFound
    }

    /**
     * The storage disk the DAM keeps asset files on.
     */
    public function assetDisk(): string
    {
        return Directory::getAssetDisk(); // @phpstan-ignore class.notFound
    }

    /**
     * Whether the caller is limited to some directories rather than seeing all of them.
     */
    public function restricted(): bool
    {
        $service = $this->service();

        return $service !== null && ! $service->bypass(); // @phpstan-ignore class.notFound
    }

    /**
     * The directories whose assets the caller may open, or null when unrestricted.
     *
     * @return list<int>|null
     */
    public function accessibleIds(): ?array
    {
        return $this->restricted() ? $this->service()->directlyGrantedIds() : null; // @phpstan-ignore class.notFound
    }

    /**
     * The directories the caller may see in the tree, which adds the ancestors of the
     * accessible ones so they can be reached, or null when unrestricted.
     *
     * @return list<int>|null
     */
    public function viewableIds(): ?array
    {
        return $this->restricted() ? $this->service()->viewableIds() : null; // @phpstan-ignore class.notFound
    }

    /**
     * The DAM's directory permission service, when the installed DAM version has one.
     */
    protected function service(): ?DirectoryPermissionService // @phpstan-ignore class.notFound
    {
        if (! class_exists(DirectoryPermissionService::class)) {
            return null;
        }

        return app(DirectoryPermissionService::class);
    }
}
