<?php

namespace Webkul\MCP\Registry;

use Laravel\Mcp\Server\Tool;
use Webkul\MCP\Tools\Catalog\AttributeFamilySearchTool;
use Webkul\MCP\Tools\Catalog\AttributeFamilyUpsertTool;
use Webkul\MCP\Tools\Catalog\AttributeGroupSearchTool;
use Webkul\MCP\Tools\Catalog\AttributeGroupUpsertTool;
use Webkul\MCP\Tools\Catalog\AttributeOptionSearchTool;
use Webkul\MCP\Tools\Catalog\AttributeSearchTool;
use Webkul\MCP\Tools\Catalog\AttributeUpsertTool;
use Webkul\MCP\Tools\Catalog\CatalogSchemaTool;
use Webkul\MCP\Tools\Catalog\CategorySearchTool;
use Webkul\MCP\Tools\Catalog\CategoryUpsertTool;
use Webkul\MCP\Tools\Catalog\ProductGetTool;
use Webkul\MCP\Tools\Catalog\ProductSearchTool;
use Webkul\MCP\Tools\Catalog\ProductUpsertTool;
use Webkul\MCP\Tools\Dam\AssetGetTool;
use Webkul\MCP\Tools\Dam\AssetSearchTool;
use Webkul\MCP\Tools\Dam\DirectoryTreeTool;
use Webkul\MCP\Tools\DataTransfer\JobExecutionTool;
use Webkul\MCP\Tools\DataTransfer\JobSearchTool;
use Webkul\MCP\Tools\Dev\AppInfoTool;
use Webkul\MCP\Tools\Dev\DatabaseQueryTool;
use Webkul\MCP\Tools\Dev\DatabaseSchemaTool;
use Webkul\MCP\Tools\Dev\DevToolsTool;
use Webkul\MCP\Tools\Dev\LogReadTool;
use Webkul\MCP\Tools\Dev\RunSkillTool;
use Webkul\MCP\Tools\Settings\CurrencySearchTool;
use Webkul\MCP\Tools\Settings\CurrencyUpsertTool;
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
            AttributeOptionSearchTool::class,

            AttributeFamilySearchTool::class,
            AttributeFamilyUpsertTool::class,

            AttributeGroupSearchTool::class,
            AttributeGroupUpsertTool::class,

            // Setting Capabilities
            SettingSearchTool::class,
            SettingUpsertTool::class,

            CurrencySearchTool::class,
            CurrencyUpsertTool::class,

            // Data Transfer Capabilities
            JobSearchTool::class,
            JobExecutionTool::class,

            // Dev & Skill Capabilities
            RunSkillTool::class,
            DevToolsTool::class,
            AppInfoTool::class,
            DatabaseSchemaTool::class,
            DatabaseQueryTool::class,
            LogReadTool::class,
        ], static::damTools());
    }

    /**
     * Tools that reach beyond the catalog into files, commands, SQL and logs.
     *
     * @return array<int, class-string<Tool>>
     */
    public static function developerTools(): array
    {
        return [
            RunSkillTool::class,
            DevToolsTool::class,
            AppInfoTool::class,
            DatabaseSchemaTool::class,
            DatabaseQueryTool::class,
            LogReadTool::class,
        ];
    }

    /**
     * DAM tools, present only when the optional unopim/dam package is
     * installed. The MCP package does not depend on the DAM, so the class
     * check is the extension seam: no DAM, no tools, no error.
     *
     * @return array<int, class-string<Tool>>
     */
    protected static function damTools(): array
    {
        if (! class_exists(\Webkul\DAM\Models\Asset::class)) {
            return [];
        }

        return [
            AssetSearchTool::class,
            AssetGetTool::class,
            DirectoryTreeTool::class,
        ];
    }
}
