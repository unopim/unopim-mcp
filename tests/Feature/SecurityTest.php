<?php

use Illuminate\Support\Facades\Config;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Webkul\MCP\DevTools\FileManager;
use Webkul\MCP\Servers\Methods\PimCallTool;

it('blocks unauthorized tool calls when api auth is enabled', function () {
    Config::set('mcp.api_auth', true);

    $request = new JsonRpcRequest('1', 'callTool', ['name' => 'upsert_products', 'arguments' => ['id' => 1]]);
    $context = $this->createMock(ServerContext::class);

    $tool = app(PimCallTool::class);
    $response = $tool->handle($request, $context);
    $data = $response->toArray();

    expect($data['result']['isError'])->toBeTrue();
    expect($data['result']['content'][0]['text'])->toContain('Unauthorized');
});

it('blocks path traversal attempts in file manager', function () {
    $manager = new FileManager;

    expect(fn () => $manager->read('../../etc/passwd'))
        ->toThrow(RuntimeException::class, 'Security');
});

it('blocks absolute path outside project in file manager', function () {
    $manager = new FileManager;

    expect(fn () => $manager->read('/etc/passwd'))
        ->toThrow(RuntimeException::class, 'Security');
});

it('allows valid paths within project boundaries', function () {
    $manager = new FileManager;
    $path = 'storage/test_mcp.txt';
    $content = 'safe content';

    if (file_exists(base_path($path))) {
        unlink(base_path($path));
    }

    $manager->create($path, $content);

    expect($manager->read($path))->toBe($content);

    unlink(base_path($path));
});
