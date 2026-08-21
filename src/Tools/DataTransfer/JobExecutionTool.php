<?php

namespace Webkul\MCP\Tools\DataTransfer;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class JobExecutionTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Retrieve details and status for a specific job execution (JobTrack) in UnoPim.';

    /**
     * The tool's name.
     */
    public string $name = 'get_job_execution';

    public function __construct(
        protected JobTrackRepository $jobTrackRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $execution = $this->jobTrackRepository->find($validated['id']);

        if (! $execution) {
            return Response::error('Job execution not found.');
        }

        return Response::json([
            'id' => $execution->id,
            'job_instances_id' => $execution->job_instances_id,
            'state' => $execution->state,
            'processed_rows_count' => $execution->processed_rows_count,
            'invalid_rows_count' => $execution->invalid_rows_count,
            'errors_count' => $execution->errors_count,
            'summary' => $execution->summary,
            'started_at' => $execution->started_at?->toIso8601String(),
            'completed_at' => $execution->completed_at?->toIso8601String(),
            'errors' => $execution->errors,
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The ID of the job execution to retrieve.')
                ->required(),
        ];
    }
}
