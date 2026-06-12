<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\MCP\Http\Middleware\AuthenticateAdminFromApiGuard;
use Webkul\User\Models\Admin;

it('propagates the api guard user onto the admin guard', function () {
    $admin = Admin::factory()->create();

    $request = Request::create('/mcp/unopim', 'POST');
    $request->setUserResolver(fn ($guard = null) => $guard === 'api' ? $admin : null);

    expect(Auth::guard('admin')->check())->toBeFalse();

    (new AuthenticateAdminFromApiGuard)->handle($request, fn ($req) => response('ok'));

    expect(Auth::guard('admin')->check())->toBeTrue()
        ->and(Auth::guard('admin')->id())->toBe($admin->id);
});

it('leaves the admin guard untouched when no api user is present', function () {
    $request = Request::create('/mcp/unopim', 'POST');
    $request->setUserResolver(fn ($guard = null) => null);

    (new AuthenticateAdminFromApiGuard)->handle($request, fn ($req) => response('ok'));

    expect(Auth::guard('admin')->check())->toBeFalse();
});

it('does not overwrite an already authenticated admin', function () {
    $sessionAdmin = Admin::factory()->create();
    $tokenAdmin = Admin::factory()->create();

    Auth::guard('admin')->setUser($sessionAdmin);

    $request = Request::create('/mcp/unopim', 'POST');
    $request->setUserResolver(fn ($guard = null) => $guard === 'api' ? $tokenAdmin : null);

    (new AuthenticateAdminFromApiGuard)->handle($request, fn ($req) => response('ok'));

    expect(Auth::guard('admin')->id())->toBe($sessionAdmin->id);
});
