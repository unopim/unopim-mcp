<?php

namespace Webkul\MCP\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\OAuthServerException;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use Symfony\Component\HttpFoundation\Response;

class RequireS256CodeChallenge
{
    /**
     * Reject authorization requests that send a PKCE challenge with any method but S256.
     *
     * OAuth 2.1 and the MCP authorization spec forbid the plain method, which League
     * otherwise assumes when the method is omitted.
     *
     * @throws OAuthServerException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query('code_challenge') !== null && $request->query('code_challenge_method') !== 'S256') {
            throw new OAuthServerException(LeagueException::invalidRequest(
                'code_challenge_method',
                trans('mcp::app.oauth.pkce-s256-required'),
            ));
        }

        return $next($request);
    }
}
