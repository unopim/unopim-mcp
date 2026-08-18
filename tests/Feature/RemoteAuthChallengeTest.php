<?php

use Illuminate\Support\Facades\Config;

/**
 * Remote clients recover from an expired token by following the 401 and its
 * `WWW-Authenticate` challenge. A redirect to the login page carries no such
 * signal, so the connector simply drops — which is what happens when the token
 * is rejected before `laravel/mcp` has reordered the `Accept` header and
 * `expectsJson()` still sees `text/event-stream` first.
 */
beforeEach(function () {
    Config::set('mcp.api_auth', true);
});

function mcpHandshake(): array
{
    return [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-06-18',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test-client', 'version' => '1.0.0'],
        ],
    ];
}

it('rejects an unauthenticated request with 401 rather than a login redirect', function () {
    $response = $this->json('POST', 'mcp/unopim', mcpHandshake(), [
        'Accept' => 'text/event-stream, application/json',
    ]);

    expect($response->getStatusCode())->not->toBe(302);

    $response->assertStatus(401);
});

it('advertises the bearer challenge so the client can re-authorize', function () {
    $response = $this->json('POST', 'mcp/unopim', mcpHandshake(), [
        'Accept' => 'text/event-stream, application/json',
    ]);

    expect($response->headers->get('WWW-Authenticate'))->toContain('Bearer');
});

it('still rejects when the client sends no accept header at all', function () {
    $response = $this->json('POST', 'mcp/unopim', mcpHandshake(), ['Accept' => '']);

    $response->assertStatus(401);
});

it('serves the request when api auth is disabled', function () {
    Config::set('mcp.api_auth', false);

    $response = $this->json('POST', 'mcp/unopim', mcpHandshake(), [
        'Accept' => 'text/event-stream, application/json',
    ]);

    expect($response->getStatusCode())->not->toBe(401);
});
