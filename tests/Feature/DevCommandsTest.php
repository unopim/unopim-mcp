<?php

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Webkul\MCP\DevTools\PluginGenerator;
use Webkul\MCP\DevTools\TestGenerator;

it('runs mcp:install command', function () {
    Artisan::call('mcp:install');
    $output = Artisan::output();

    expect($output)->toContain('UnoPim MCP setup completed');
});

it('runs mcp:make plugin command', function () {
    $this->mock(PluginGenerator::class)
        ->shouldReceive('generate')
        ->once()
        ->with('MyConnector', 'connector');

    Artisan::call('mcp:make', [
        'action' => 'plugin',
        'name' => 'MyConnector',
        '--type' => 'connector',
    ]);

    expect(Artisan::output())->toContain('Plugin MyConnector generated successfully');
});

it('runs mcp:make test command', function () {
    $this->mock(TestGenerator::class)
        ->shouldReceive('generate')
        ->once()
        ->with('MyConnector', 'Services/MyService')
        ->andReturn('packages/Webkul/MyConnector/tests/Unit/Services/MyServiceTest.php');

    Artisan::call('mcp:make', [
        'action' => 'test',
        'name' => 'MyConnector',
        'target' => 'Services/MyService',
    ]);

    expect(Artisan::output())->toContain('Test generated at:');
});

it('rejects invalid action in mcp:make', function () {
    Artisan::call('mcp:make', [
        'action' => 'invalid',
    ]);

    expect(Artisan::output())->toContain('Invalid action');
});

it('mcp:inspector outputs launch message', function () {
    Process::fake();

    Artisan::call('mcp:inspector', [
        'server' => 'unopim-dev',
        '--port' => 9999,
    ]);

    expect(Artisan::output())->toContain('Launching MCP Inspector');

    Process::assertRan(fn (PendingProcess $process): bool => in_array('@modelcontextprotocol/inspector', (array) $process->command, true)
        && $process->environment['PORT'] === 9999);
});
