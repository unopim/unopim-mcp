<?php

namespace Webkul\MCP\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LimitClientRegistration
{
    /**
     * Reject dynamic client registrations whose body or redirect URIs exceed the configured caps.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $limits = config('mcp.oauth.registration');

        $maxBodyBytes = (int) ($limits['max_body_bytes'] ?? 16384);

        if (max((int) $request->header('Content-Length'), strlen($request->getContent())) > $maxBodyBytes) {
            return $this->invalid('body-too-large', $maxBodyBytes);
        }

        $redirectUris = $request->input('redirect_uris');

        if (! is_array($redirectUris)) {
            return $next($request);
        }

        $maxRedirectUris = (int) ($limits['max_redirect_uris'] ?? 10);

        if (count($redirectUris) > $maxRedirectUris) {
            return $this->invalid('too-many-redirect-uris', $maxRedirectUris);
        }

        $maxUriLength = (int) ($limits['max_redirect_uri_length'] ?? 2048);

        foreach ($redirectUris as $uri) {
            if (is_string($uri) && strlen($uri) > $maxUriLength) {
                return $this->invalid('redirect-uri-too-long', $maxUriLength);
            }
        }

        return $next($request);
    }

    protected function invalid(string $reason, int $max): JsonResponse
    {
        return response()->json([
            'error' => 'invalid_client_metadata',
            'error_description' => trans("mcp::app.oauth.registration.{$reason}", ['max' => $max]),
        ], 400);
    }
}
