<?php

namespace Webkul\MCP\Registry;

use Webkul\MCP\Tools\Catalog\AttributeSearchTool;
use Webkul\MCP\Tools\Catalog\AttributeUpsertTool;
use Webkul\MCP\Tools\Catalog\CatalogSchemaTool;
use Webkul\MCP\Tools\Catalog\CategorySearchTool;
use Webkul\MCP\Tools\Catalog\CategoryUpsertTool;
use Webkul\MCP\Tools\Catalog\ProductGetTool;
use Webkul\MCP\Tools\Catalog\ProductSearchTool;
use Webkul\MCP\Tools\Catalog\ProductUpsertTool;
use Webkul\MCP\Tools\Dev\DevToolsTool;
use Webkul\MCP\Tools\Dev\RunSkillTool;
use Webkul\MCP\Tools\Settings\SettingSearchTool;
use Webkul\MCP\Tools\Settings\SettingUpsertTool;

class ToolRegistry
{
    /**
     * Get all registered tools.
     *
     * @return array<int, Tool|class-string<Tool>>
     */
    public static function tools(): array
    {
        return array_merge([
            // Catalog Discovery & Schema
            CatalogSchemaTool::class,

            // Core Catalog Capabilities
            ProductSearchTool::class,
            ProductGetTool::class,
            ProductUpsertTool::class,

            CategorySearchTool::class,
            CategoryUpsertTool::class,

            AttributeSearchTool::class,
            AttributeUpsertTool::class,

            // Setting Capabilities
            SettingSearchTool::class,
            SettingUpsertTool::class,

            // Dev & Skill Capabilities
            RunSkillTool::class,
            DevToolsTool::class,
        ], static::damTools());
    }

    /**
     * DAM tools, present only when the optional unopim/dam package is
     * installed. The MCP package does not depend on the DAM, so the class
     * check is the extension seam: no DAM, no tools, no error.
     *
     * @return array<int, class-string>
     */
    protected static function damTools(): array
    {
        if (! class_exists(\Webkul\DAM\Models\Asset::class)) {
            return [];
        }

        return [
            \Webkul\MCP\Tools\Dam\AssetSearchTool::class,
            \Webkul\MCP\Tools\Dam\AssetGetTool::class,
            \Webkul\MCP\Tools\Dam\DirectoryTreeTool::class,
        ];
    }
}
