<?php

namespace Webkul\MCP\Registry;

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
        return [
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
        ];
    }
}
