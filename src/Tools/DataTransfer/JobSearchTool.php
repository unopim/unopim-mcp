<?php

namespace Webkul\MCP\Tools\DataTransfer;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class JobSearchTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Search for import/export job instances in UnoPim using generic filters and pagination. Each job includes its latest_execution (id, state, started_at, completed_at, or null if it never ran); pass latest_execution.id to get_job_execution for row counts and errors. latest_execution is omitted when the caller lacks job tracker access.';

    /**
     * The tool's name.
     */
    public string $name = 'search_jobs';

    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
        protected UnoPimQueryBuilder $queryBuilder
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'filters' => ['nullable', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string'],
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

        $latestExecutions = $this->allows('data_transfer.job_tracker')
            ? $this->latestExecutions($paginator->pluck('id'))
            : null;

        return Response::json([
            'count' => $paginator->count(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'has_more' => $paginator->hasMorePages(),
            'jobs' => $paginator->map(fn ($j): array => [
                'id' => $j->id,
                'code' => $j->code,
                'type' => $j->type,
                'entity_type' => $j->entity_type,
                'action' => $j->action,
                ...($latestExecutions === null ? [] : ['latest_execution' => $latestExecutions->get($j->id)]),
            ])->values()->all(),
        ]);
    }

    /**
     * The newest execution of each given job instance, keyed by job instance id, in one query.
     *
     * @param  Collection<int, int>  $jobIds
     * @return Collection<int, array{id: int, state: string, started_at: ?string, completed_at: ?string}>
     */
    protected function latestExecutions(Collection $jobIds): Collection
    {
        if ($jobIds->isEmpty()) {
            return collect();
        }

        $model = $this->jobTrackRepository->getModel();

        return $model->newQuery()
            ->whereIn($model->getKeyName(), fn (Builder $query) => $query
                ->selectRaw('max('.$query->getGrammar()->wrap($model->getKeyName()).')')
                ->from($model->getTable())
                ->whereIn('job_instances_id', $jobIds->all())
                ->groupBy('job_instances_id'))
            ->get(['id', 'job_instances_id', 'state', 'started_at', 'completed_at'])
            ->mapWithKeys(fn ($execution): array => [$execution->job_instances_id => [
                'id' => $execution->id,
                'state' => $execution->state,
                'started_at' => $execution->started_at?->toIso8601String(),
                'completed_at' => $execution->completed_at?->toIso8601String(),
            ]]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filters' => $this->filtersSchema(
                $schema,
                'List of filters: [{field, operator, value}].',
                'The field to filter by (e.g., code, type, entity_type).',
            ),
            'limit' => $schema->integer()
                ->description('Number of results per page.')
                ->default(25),
            'cursor' => $schema->string()
                ->description('Pagination cursor.'),
        ];
    }
}
