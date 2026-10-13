<?php

use Illuminate\Support\Facades\Config;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Webkul\MCP\DevTools\FileManager;
use Webkul\MCP\Servers\Methods\PimCallTool;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('blocks unauthorized tool calls when api auth is enabled', function () {
    Config::set('mcp.api_auth', true);

    $request = new JsonRpcRequest('1', 'callTool', ['name' => 'upsert_products', 'arguments' => ['id' => 1]]);
    $context = $this->createStub(ServerContext::class);

    $tool = app(PimCallTool::class);
    $response = $tool->handle($request, $context);
    $data = $response->toArray();

    expect($data['result']['isError'])->toBeTrue();
    expect($data['result']['content'][0]['text'])->toBe(trans('mcp::app.call-tool.forbidden', ['tool' => 'upsert_products']));
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

it('stops a create only admin from updating an existing product through upsert_products', function () {
    Config::set('mcp.api_auth', true);

    $product = Product::factory()->simple()->create(['status' => 1]);
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => ['catalog', 'catalog.products', 'catalog.products.create'],
    ]);

    auth()->guard('admin')->setUser(Admin::factory()->create(['role_id' => $role->id]));

    $request = new JsonRpcRequest('1', 'tools/call', [
        'name' => 'upsert_products',
        'arguments' => ['products' => [['sku' => $product->sku, 'status' => false]]],
    ]);

    $data = app(PimCallTool::class)->handle($request, $this->createStub(ServerContext::class))->toArray();

    expect($data['result']['isError'])->toBeTrue()
        ->and($data['result']['content'][0]['text'])->toBe(trans('mcp::app.call-tool.forbidden', ['tool' => 'upsert_products']))
        ->and((int) $product->fresh()->status)->toBe(1);
});
