<?php

namespace Webkul\MCP\Tools\DataTransfer;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\MCP\Tools\BaseMcpTool;
use Webkul\MCP\Services\UnoPimQueryBuilder;

class JobSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search for import/export job instances in UnoPim using generic filters and pagination.';

    /**
     * The tool's name.
     */
    public string $name = 'search_jobs';

    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected UnoPimQueryBuilder $queryBuilder
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'filters' => ['nullable', 'array'],
            'limit'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'  => ['nullable', 'string'],
        ]);

        $query = $this->jobInstancesRepository->getModel()->query();

        if (! empty($validated['filters'])) {
            $this->queryBuilder->applyFilters($query, $validated['filters']);
        }

        $paginator = $this->queryBuilder->paginate(
            $query->orderByDesc('id'),
            (int) ($validated['limit'] ?? 25),
            $validated['cursor'] ?? null
        );

        return Response::json([
            'count'       => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more'    => $paginator->hasMorePages(),
            'jobs'        => $paginator->map(fn ($j) => [
                'id'          => $j->id,
                'code'        => $j->code,
                'type'        => $j->type,
                'entity_type' => $j->entity_type,
                'action'      => $j->action,
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
                        'field'    => $schema->string()->description('The field to filter by (e.g., code, type, entity_type).'),
                        'operator' => $schema->string()->description('The comparison operator.'),
                        'value'    => $schema->string()->description('The value to compare against. For IN and NOT IN, pass a comma-separated list ("a,b") or an array.'),
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
