<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Webkul\MCP\Servers\Methods\PimCallTool;

it('returns rate limit error response with correct structure', function () {
    $toolName = 'search_products';

    RateLimiter::shouldReceive('tooManyAttempts')
        ->withArgs(fn ($key, $max) => str_contains($key, "mcp-tool:{$toolName}") && $max === 60)
        ->once()
        ->andReturn(true);

    $request = new JsonRpcRequest('1', 'callTool', ['name' => $toolName, 'arguments' => []]);
    $context = Mockery::mock(ServerContext::class);

    $tool = app(PimCallTool::class);
    $response = $tool->handle($request, $context);
    $data = $response->toArray();

    expect($data['result']['isError'])->toBeTrue();
    expect($data['result']['content'][0]['text'])->toContain('Rate limit exceeded');
});

it('returns unauthorized error when api_auth is enabled and no user', function () {
    Config::set('mcp.api_auth', true);

    RateLimiter::shouldReceive('tooManyAttempts')->andReturn(false);
    RateLimiter::shouldReceive('hit')->once();

    $request = new JsonRpcRequest('2', 'callTool', ['name' => 'upsert_products', 'arguments' => []]);
    $context = Mockery::mock(ServerContext::class);

    $tool = app(PimCallTool::class);
    $response = $tool->handle($request, $context);
    $data = $response->toArray();

    expect($data['result']['isError'])->toBeTrue();
    expect($data['result']['content'][0]['text'])->toContain('Unauthorized');
});

it('does not log audit for read-only tools', function () {
    Log::shouldReceive('info')->never();

    $tool = app(PimCallTool::class);
    $auditMethod = new ReflectionMethod(PimCallTool::class, 'auditLog');
    $auditMethod->setAccessible(true);

    $readOnlyTools = [
        'get_catalog_schema',
        'search_products',
        'get_product',
        'search_categories',
        'search_attributes',
        'search_settings',
    ];

    foreach ($readOnlyTools as $toolName) {
        $auditMethod->invoke($tool, $toolName, []);
    }
});

it('logs audit for write tools', function () {
    Log::shouldReceive('info')
        ->times(4)
        ->withArgs(fn ($msg) => str_contains($msg, 'MCP Audit Log'));

    $tool = app(PimCallTool::class);
    $auditMethod = new ReflectionMethod(PimCallTool::class, 'auditLog');
    $auditMethod->setAccessible(true);

    $writeTools = [
        'upsert_products',
        'upsert_categories',
        'upsert_attributes',
        'upsert_settings',
    ];

    foreach ($writeTools as $toolName) {
        $auditMethod->invoke($tool, $toolName, []);
    }
});

it('respects configurable rate limit value', function () {
    Config::set('mcp.rate_limit', 10);

    RateLimiter::shouldReceive('tooManyAttempts')
        ->withArgs(fn ($key, $max) => $max === 10)
        ->once()
        ->andReturn(true);

    $request = new JsonRpcRequest('4', 'callTool', ['name' => 'search_products', 'arguments' => []]);
    $context = Mockery::mock(ServerContext::class);

    $tool = app(PimCallTool::class);
    $response = $tool->handle($request, $context);
    $data = $response->toArray();

    expect($data['result']['isError'])->toBeTrue();
});

it('extracts tool name from request params', function () {
    RateLimiter::shouldReceive('tooManyAttempts')
        ->withArgs(fn ($key) => str_contains($key, 'mcp-tool:get_product'))
        ->once()
        ->andReturn(true);

    $request = new JsonRpcRequest('5', 'callTool', ['name' => 'get_product', 'arguments' => ['id' => 1]]);
    $context = Mockery::mock(ServerContext::class);

    $tool = app(PimCallTool::class);
    $tool->handle($request, $context);
});

it('defaults to unknown when tool name is missing', function () {
    RateLimiter::shouldReceive('tooManyAttempts')
        ->withArgs(fn ($key) => str_contains($key, 'mcp-tool:unknown'))
        ->once()
        ->andReturn(true);

    $request = new JsonRpcRequest('6', 'callTool', ['arguments' => []]);
    $context = Mockery::mock(ServerContext::class);

    $tool = app(PimCallTool::class);
    $tool->handle($request, $context);
});
