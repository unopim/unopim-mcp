<?php

use Illuminate\Support\Facades\DB;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\DataTransfer\JobExecutionTool;
use Webkul\MCP\Tools\DataTransfer\JobSearchTool;

it('searches job instances through the mcp tool', function () {
    UnoPimAgentServer::tool(JobSearchTool::class)
        ->assertOk()
        ->assertSee('jobs');
});

it('gets job execution details', function () {
    // Create a dummy job instance if none exists
    $job = JobInstances::first() ?? JobInstances::create([
        'code' => 'test_job',
        'entity_type' => 'products',
        'type' => 'import',
        'action' => 'append',
    ]);

    // Create a dummy job track (execution) record
    $execution = JobTrack::create([
        'job_instances_id' => $job->id,
        'type' => $job->type,
        'action' => $job->action,
        'validation_strategy' => $job->validation_strategy ?? Import::VALIDATION_STRATEGY_SKIP_ERRORS,
        'state' => 'completed',
        'meta' => [],
        'processed_rows_count' => 10,
        'errors_count' => 0,
    ]);

    UnoPimAgentServer::tool(JobExecutionTool::class, [
        'id' => $execution->id,
    ])->assertOk()
        ->assertSee('completed')
        ->assertSee('10');

    // Cleanup
    $execution->delete();
});

it('returns the latest execution of each job so its id can be passed to get_job_execution', function () {
    $job = JobInstances::factory()->importJob()->create();

    JobTrack::factory()->create([
        'job_instances_id' => $job->id,
        'state' => 'failed',
        'started_at' => now()->subDay(),
        'completed_at' => now()->subDay(),
    ]);

    $latest = JobTrack::factory()->create([
        'job_instances_id' => $job->id,
        'state' => 'completed',
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $payload = $this->toolPayload(JobSearchTool::class, [
        'filters' => [['field' => 'code', 'operator' => '=', 'value' => $job->code]],
    ]);

    expect($payload['jobs'])->toHaveCount(1)
        ->and($payload['jobs'][0]['latest_execution'])->toBe([
            'id' => $latest->id,
            'state' => 'completed',
            'started_at' => $latest->started_at->toIso8601String(),
            'completed_at' => $latest->completed_at->toIso8601String(),
        ]);
});

it('returns a null latest execution for a job that has never run', function () {
    $job = JobInstances::factory()->importJob()->create();

    $payload = $this->toolPayload(JobSearchTool::class, [
        'filters' => [['field' => 'code', 'operator' => '=', 'value' => $job->code]],
    ]);

    expect($payload['jobs'])->toHaveCount(1)
        ->and($payload['jobs'][0])->toHaveKey('latest_execution')
        ->and($payload['jobs'][0]['latest_execution'])->toBeNull();
});

it('loads the latest executions of a page of jobs in a single query', function () {
    $jobs = JobInstances::factory()->importJob()->count(3)->create();

    foreach ($jobs as $job) {
        JobTrack::factory()->count(2)->create(['job_instances_id' => $job->id]);
    }

    $trackTable = DB::getTablePrefix().(new JobTrack)->getTable();
    $trackQueries = 0;

    DB::listen(function ($query) use (&$trackQueries, $trackTable) {
        if (str_contains($query->sql, $trackTable)) {
            $trackQueries++;
        }
    });

    $this->toolPayload(JobSearchTool::class, [
        'filters' => [['field' => 'id', 'operator' => 'IN', 'value' => $jobs->pluck('id')->all()]],
    ]);

    expect($trackQueries)->toBe(1);
});

it('reports a missing job execution with a translated message', function () {
    app()->setLocale('fr_FR');

    UnoPimAgentServer::tool(JobExecutionTool::class, ['id' => 999999])
        ->assertHasErrors([trans('mcp::app.tools.job-execution.not-found')]);

    expect(trans('mcp::app.tools.job-execution.not-found'))->toBe("L'exécution de la tâche est introuvable.");
});

it('rejects a job execution id outside the integer column range before querying', function () {
    UnoPimAgentServer::tool(JobExecutionTool::class, ['id' => PHP_INT_MAX])
        ->assertHasErrors([trans('validation.max.numeric', ['attribute' => 'id', 'max' => 2147483647])]);
});
