<?php

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
        'state' => 'completed',
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
