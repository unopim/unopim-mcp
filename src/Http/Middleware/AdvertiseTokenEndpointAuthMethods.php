<?php

namespace Webkul\MCP\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdvertiseTokenEndpointAuthMethods
{
    /**
     * Add the token endpoint auth methods to the authorization server metadata.
     *
     * Dynamically registered clients are public PKCE clients, and ChatGPT requires
     * the metadata to say so; a value already published upstream is kept.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse || ! $response->isSuccessful()) {
            return $response;
        }

        $metadata = $response->getData(true);

        if (is_array($metadata) && ! isset($metadata['token_endpoint_auth_methods_supported'])) {
            $response->setData($metadata + ['token_endpoint_auth_methods_supported' => ['none']]);
        }

        return $response;
    }
}
