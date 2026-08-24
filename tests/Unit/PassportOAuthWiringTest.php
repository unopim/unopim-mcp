<?php

use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Webkul\MCP\Providers\MCPServiceProvider;

/**
 * Runs configurePassportGuard() against the current config, the way the
 * provider does during register().
 */
function configureGuard(): void
{
    $provider = new MCPServiceProvider(app());

    $method = (new ReflectionClass($provider))->getMethod('configurePassportGuard');
    $method->setAccessible(true);
    $method->invoke($provider);
}

it('moves passport onto the admin guard when the configured guard does not authenticate admins', function () {
    config([
        'passport.guard' => 'web',
        'auth.guards.web' => ['driver' => 'session', 'provider' => 'users'],
        'auth.guards.admin' => ['driver' => 'session', 'provider' => 'admins'],
    ]);

    configureGuard();

    expect(config('passport.guard'))->toBe('admin');
});

it('leaves an explicit guard alone when it already authenticates admins', function () {
    config([
        'passport.guard' => 'custom-admin',
        'auth.guards.custom-admin' => ['driver' => 'session', 'provider' => 'admins'],
        'auth.guards.admin' => ['driver' => 'session', 'provider' => 'admins'],
    ]);

    configureGuard();

    expect(config('passport.guard'))->toBe('custom-admin');
});

it('does nothing when there is no admin guard to switch to', function () {
    config([
        'passport.guard' => 'web',
        'auth.guards.web' => ['driver' => 'session', 'provider' => 'users'],
        'auth.guards.admin' => null,
    ]);

    configureGuard();

    expect(config('passport.guard'))->toBe('web');
});

it('binds an authorization view so the consent screen can render', function () {
    expect(app()->bound(AuthorizationViewResponse::class))->toBeTrue();
});

it('ships the consent view under the package namespace', function () {
    expect(view()->exists('unopim-mcp::authorize'))->toBeTrue();
});
