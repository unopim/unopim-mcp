<?php

namespace Webkul\MCP\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcpRequest extends Authenticate
{
    /**
     * Authenticate the MCP request when `mcp.api_auth` is enabled.
     *
     * The setting is read per request rather than when routes load, and a client that
     * sends no Accept header is treated as JSON so a rejected token answers with the
     * 401 challenge remote clients re-authorize from, never a login redirect.
     *
     * @param  string  ...$guards
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        if (! config('mcp.api_auth', true)) {
            return $next($request);
        }

        if (! $request->headers->get('Accept')) {
            $request->headers->set('Accept', 'application/json');
        }

        return parent::handle($request, $next, ...($guards ?: ['api']));
    }

    /**
     * Never redirect an unauthenticated MCP request.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
