<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;

class AttributeGroupSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search for attribute groups in the UnoPim catalog using generic filters and pagination.';

    /**
     * The tool's name.
     */
    public string $name = 'search_attribute_groups';

    public function __construct(
        protected AttributeGroupRepository $groupRepository,
        protected UnoPimQueryBuilder $queryBuilder
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'filters' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
        ]);

        $query = $this->groupRepository->getModel()->query();

        if (! empty($validated['filters'])) {
            $this->queryBuilder->applyFilters($query, $validated['filters']);
        }

        $paginator = $this->queryBuilder->paginate(
            $query->orderByDesc('id'),
            (int) ($validated['limit'] ?? 25),
            $validated['cursor'] ?? null
        );

        return Response::json([
            'count' => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'groups' => $paginator->map(fn ($g) => [
                'id' => $g->id,
                'code' => $g->code,
                'name' => $g->name,
            ])->values()->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filters' => $schema->array()
                ->description('List of filters: [{field, operator, value}].')
                ->items(
                    $schema->object([
                        'field' => $schema->string()->description('The field to filter by (e.g., code, name).'),
                        'operator' => $schema->string()->description('The comparison operator.'),
                        'value' => $schema->string()->description('The value to compare against.'),
                    ])
                ),
            'limit' => $schema->integer()
                ->description('Number of results per page.')
                ->default(25),
            'cursor' => $schema->string()
                ->description('Pagination cursor.'),
        ];
    }
}
