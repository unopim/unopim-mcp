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
     * Every tool the package provides, grouped so an installation can decide
     * which capabilities to expose. Group names match the keys under
     * config('mcp.tools').
     *
     * @return array<string, array<int, class-string>>
     */
    public static function groups(): array
    {
        return [
            'catalog' => [
                // Discovery & schema
                CatalogSchemaTool::class,

                // Products
                ProductSearchTool::class,
                ProductGetTool::class,
                ProductUpsertTool::class,

                // Categories
                CategorySearchTool::class,
                CategoryUpsertTool::class,

                // Attributes, their options, groups and families
                AttributeSearchTool::class,
                AttributeUpsertTool::class,
                AttributeOptionSearchTool::class,
                AttributeGroupSearchTool::class,
                AttributeGroupUpsertTool::class,
                AttributeFamilySearchTool::class,
                AttributeFamilyUpsertTool::class,
            ],

            'settings' => [
                SettingSearchTool::class,
                SettingUpsertTool::class,
                CurrencySearchTool::class,
                CurrencyUpsertTool::class,
            ],

            'data_transfer' => [
                JobSearchTool::class,
                JobExecutionTool::class,
            ],

            /*
             * These reach outside the catalogue: arbitrary SQL, log contents,
             * file writes and command execution. Useful on a local development
             * instance, and not something to expose on a host reachable from
             * the internet, so the group is opt-in.
             */
            'developer' => [
                AppInfoTool::class,
                DatabaseSchemaTool::class,
                DatabaseQueryTool::class,
                LogReadTool::class,
                RunSkillTool::class,
                DevToolsTool::class,
            ],
        ];
    }

    /**
     * Get all registered tools.
     *
     * @return array<int, Tool|class-string<Tool>>
     */
    public static function tools(): array
    {
        $enabled = [];

        foreach (static::groups() as $group => $tools) {
            if (! config("mcp.tools.{$group}", true)) {
                continue;
            }

            $enabled = array_merge($enabled, $tools);
        }

        return $enabled;
    }
}
