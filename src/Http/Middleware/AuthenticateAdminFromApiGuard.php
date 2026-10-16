<?php

namespace Webkul\MCP\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminFromApiGuard
{
    /**
     * Propagate the Passport-authenticated admin onto the session "admin"
     * guard so ACL checks (bouncer()) work for token-based MCP requests.
     *
     * A token issued to an admin who has since been disabled is refused with the
     * same 401 challenge as an invalid token. The admin's UI locale is applied as
     * the admin panel's Bouncer does, falling back to the application default.
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

        if ($user) {
            $this->applyUiLocale($user);
        }

        return $next($request);
    }

    protected function applyUiLocale(Authenticatable $user): void
    {
        $localeCode = method_exists($user, 'uiLocale') ? $user->uiLocale()->first()?->code : null;

        app()->setLocale($localeCode ?: config('app.locale'));
    }
}
