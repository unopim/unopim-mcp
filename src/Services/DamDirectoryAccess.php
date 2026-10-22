<?php

namespace Webkul\MCP\Services;

use Webkul\DAM\Services\DirectoryPermissionService;

/**
 * The DAM directories the current caller may open, following the DAM's own role settings.
 *
 * A custom role can be limited to a set of directories in the DAM. The DAM's admin
 * screens and REST API apply that limit through DirectoryPermissionService; the MCP
 * DAM tools apply it the same way so an MCP client never sees more than the DAM would
 * show the same user. DAM versions without the service have no directory limits.
 */
class DamDirectoryAccess
{
    /**
     * Whether the caller is limited to some directories rather than seeing all of them.
     */
    public function restricted(): bool
    {
        $service = $this->service();

        return $service !== null && ! $service->bypass();
    }

    /**
     * The directories whose assets the caller may open, or null when unrestricted.
     *
     * @return list<int>|null
     */
    public function accessibleIds(): ?array
    {
        return $this->restricted() ? $this->service()->directlyGrantedIds() : null;
    }

    /**
     * The directories the caller may see in the tree, which adds the ancestors of the
     * accessible ones so they can be reached, or null when unrestricted.
     *
     * @return list<int>|null
     */
    public function viewableIds(): ?array
    {
        return $this->restricted() ? $this->service()->viewableIds() : null;
    }

    /**
     * The DAM's directory permission service, when the installed DAM version has one.
     */
    protected function service(): ?DirectoryPermissionService
    {
        if (! class_exists(DirectoryPermissionService::class)) {
            return null;
        }

        return app(DirectoryPermissionService::class);
    }
}
