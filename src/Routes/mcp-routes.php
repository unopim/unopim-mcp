<?php

use Laravel\Mcp\Facades\Mcp;
use Webkul\MCP\Http\Middleware\AuthenticateAdminFromApiGuard;
use Webkul\MCP\Servers\UnoPimAgentServer;

/**
 * Authentication belongs on the route rather than the surrounding group.
 * Group middleware runs first, and `laravel/mcp` only reorders the `Accept`
 * header at route level — remote clients send `text/event-stream` ahead of
 * `application/json`, so a token rejected before that reorder fails
 * `expectsJson()` and renders as a redirect to the login page. Remote clients
 * need the 401 and its `WWW-Authenticate` challenge to re-authorize; a redirect
 * gives them nothing to act on and the connector drops.
 */
$middleware = [];

if (config('mcp.api_auth')) {
    $middleware[] = 'auth:api';
}

$middleware[] = AuthenticateAdminFromApiGuard::class;

Mcp::web('mcp/unopim', UnoPimAgentServer::class)->middleware($middleware);
