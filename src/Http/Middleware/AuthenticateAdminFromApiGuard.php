<?php

namespace Webkul\MCP\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminFromApiGuard
{
    /**
     * Propagate the Passport-authenticated admin onto the session "admin"
     * guard so ACL checks (bouncer()) work for token-based MCP requests.
     *
     * A token issued to an admin who has since been disabled is refused with the
     * same 401 challenge as an invalid token.
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('auth.guards.api') || ! config('auth.guards.admin')) {
            return $next($request);
        }

        $user = $request->user('api');

        if ($user && ! data_get($user, 'status', true)) {
            throw new AuthenticationException('Unauthenticated.', ['api']);
        }

        if ($user && ! auth()->guard('admin')->check()) {
            auth()->guard('admin')->setUser($user);
        }

        return $next($request);
    }
}
