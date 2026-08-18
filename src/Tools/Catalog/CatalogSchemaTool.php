<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class CatalogSchemaTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Retrieve the UnoPim catalog schema, including filterable attributes and query rules.';

    /**
     * The tool's name.
     */
    public string $name = 'get_catalog_schema';

    public function __construct(
        protected AttributeRepository $attributeRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $filterableAttributes = $this->attributeRepository->findWhere(['is_filterable' => 1], ['id', 'code', 'type']);

        return Response::json([
            'filterable_fields' => [
                'products' => $filterableAttributes->map(fn ($a) => [
                    'field' => $a->code,
                    'type' => $a->type,
                    'label' => $a->name ?? $a->code,
                ])->values()->all(),
                'categories' => ['id', 'code', 'parent_id'],
                'attributes' => ['id', 'code', 'type', 'is_required', 'is_unique', 'is_filterable'],
                'channels' => ['id', 'code'],
                'locales' => ['id', 'code', 'status'],
            ],
            'operators' => [
                '=', '!=', 'IN', 'NOT IN', 'CONTAINS', 'STARTS WITH', 'ENDS WITH', '>', '<',
            ],
            'pagination' => [
                'limit_max' => 100,
                'type' => 'cursor',
            ],
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
