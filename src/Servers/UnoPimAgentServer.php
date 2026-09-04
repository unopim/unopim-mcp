<?php

namespace Webkul\MCP\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;
use Webkul\MCP\Prompts\Catalog\CatalogAnalysisPrompt;
use Webkul\MCP\Registry\ToolRegistry;
use Webkul\MCP\Resources\Catalog\CatalogSchemaResource;
use Webkul\MCP\Servers\Methods\PimCallTool;
use Webkul\MCP\Services\SkillLoader;
use Webkul\MCP\Tools\Dev\DynamicSkillTool;

class UnoPimAgentServer extends Server
{
    /**
     * The name of the MCP server.
     */
    protected string $name = 'UnoPim MCP Agent';

    /**
     * The version of the MCP server.
     */
    protected string $version = '1.0.0';

    /**
     * The instructions for AI clients connecting to this server.
     */
    protected string $instructions = <<<'MARKDOWN'
        This MCP server connects AI coding assistants directly into the UnoPim PIM platform.

        ### Capabilities
        - **Catalog Discovery**: Use `get_catalog_schema` to understand filterable fields, operators, and pagination rules.
        - **Product Management**: Use `search_products` to find products, `get_product` for details, and `upsert_products` to create/update (batch up to 50).
        - **Category Management**: Use `search_categories` to browse and `upsert_categories` to create/update categories.
        - **Attribute Management**: Use `search_attributes` to explore attributes, `upsert_attributes` to create/update them, and `search_attribute_options` to list the options of a select/multiselect attribute.
        - **Family & Group Management**: Use `search_families` / `upsert_families` for attribute families, and `search_attribute_groups` / `upsert_attribute_groups` for the groups that organise attributes within a family.
        - **Settings Management**: Use `search_settings` and `upsert_settings` to manage channels and locales, and `search_currencies` / `upsert_currencies` to manage currencies.
        - **Data Transfer**: Use `search_jobs` to find import/export job instances and `get_job_execution` to inspect a run's status, row counts, and errors.
        - **Developer Tools**: Use `dev_tools` for file management, command execution, and code generation. Use `run_skill` to execute predefined development skills.
        - **Diagnostics**: Use `get_app_info` for Laravel/PHP versions and installed packages, `get_database_schema` to introspect tables and columns, `run_database_query` for read-only SQL, and `read_logs` to tail application logs.
        - **Dynamic Skills**: Custom skills loaded from `.ai/skills/` are registered as additional tools.

        ### Guidelines
        - Always start with `get_catalog_schema` to discover the catalog structure before querying.
        - All search tools support generic filters with operators: `=`, `!=`, `IN`, `NOT IN`, `CONTAINS`, `STARTS WITH`, `ENDS WITH`, `>`, `<`.
        - All search tools use cursor-based pagination (max 100 per page).
        - Use `dev_tools` with action `generate_plugin` to scaffold new extensions.
        - Use `dev_tools` with action `run_command` for artisan/composer commands.
        - Prefer `get_job_execution` over `read_logs` when diagnosing a failed import or export.
        - `run_database_query` is read-only — use the upsert tools to change catalog data.
        - Access the `catalog-schema` resource for a high-level catalog summary.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        CatalogSchemaResource::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        CatalogAnalysisPrompt::class,
    ];

    /**
     * Initialize the server and tools.
     */
    public function __construct(
        Transport $transport,
        protected SkillLoader $skillLoader
    ) {
        parent::__construct($transport);

        $this->tools = ToolRegistry::tools();
        $this->loadDynamicSkills();
    }

    protected function boot(): void
    {
        $this->methods['tools/call'] = PimCallTool::class;
    }

    /**
     * Load dynamic skills from .ai/skills as tools.
     */
    protected function loadDynamicSkills(): void
    {
        $skills = $this->skillLoader->all();

        foreach ($skills as $skill) {
            $this->tools[] = app(DynamicSkillTool::class, ['skillData' => $skill]);
        }
    }
}
