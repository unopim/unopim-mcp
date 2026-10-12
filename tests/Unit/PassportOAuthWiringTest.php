<?php

use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Http\Responses\SimpleViewResponse;
use Webkul\MCP\Providers\MCPServiceProvider;

function configurePassport(string $method): void
{
    (new ReflectionMethod(MCPServiceProvider::class, $method))->invoke(new MCPServiceProvider(app()));
}

it('binds the package consent screen for the passport authorize route', function () {
    expect(app()->bound(AuthorizationViewResponse::class))->toBeTrue()
        ->and(view()->exists('mcp-unopim::authorize'))->toBeTrue();
});

it('keeps a consent view the host application already bound', function () {
    $hostView = new SimpleViewResponse('welcome');

    app()->instance(AuthorizationViewResponse::class, $hostView);

    configurePassport('configurePassportViews');

    expect(app(AuthorizationViewResponse::class))->toBe($hostView);
});

it('moves passport onto the admin guard when it is left on the default web guard', function () {
    config(['passport.guard' => 'web']);

    configurePassport('configurePassportGuard');

    expect(config('passport.guard'))->toBe('admin');
});

it('respects an explicitly configured passport guard', function () {
    config(['passport.guard' => 'custom']);

    configurePassport('configurePassportGuard');

    expect(config('passport.guard'))->toBe('custom');
});
