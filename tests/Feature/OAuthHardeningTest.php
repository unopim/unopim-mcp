<?php

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function registerChatGptClient(): string
{
    return test()->postJson('/oauth/register', [
        'client_name' => 'ChatGPT',
        'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
    ])->assertSuccessful()->json('client_id');
}

function authorizeQuery(string $clientId, array $overrides = []): string
{
    return '/oauth/authorize?'.http_build_query(array_filter([
        'client_id' => $clientId,
        'redirect_uri' => 'https://chatgpt.com/connector_platform_oauth_redirect',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'state',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 64), true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
        ...$overrides,
    ], fn ($value) => $value !== null));
}

function actingAsMcpAdmin(): void
{
    test()->actingAs(Admin::factory()->create([
        'role_id' => Role::factory()->create(['permission_type' => 'all'])->id,
    ]), 'admin');
}

dataset('non-S256 code challenge methods', [
    'plain' => ['plain'],
    'missing' => [null],
    'unknown' => ['S512'],
]);

it('rejects an authorization request whose code challenge method is not S256', function (?string $method) {
    $clientId = registerChatGptClient();

    actingAsMcpAdmin();

    $this->get(authorizeQuery($clientId, [
        'code_challenge' => str_repeat('v', 64),
        'code_challenge_method' => $method,
    ]))
        ->assertStatus(400)
        ->assertJsonPath('error', 'invalid_request')
        ->assertJsonPath('hint', trans('mcp::app.oauth.pkce-s256-required'));
})->with('non-S256 code challenge methods');

it('rejects a plain code challenge before the user signs in', function () {
    $this->get(authorizeQuery(registerChatGptClient(), [
        'code_challenge' => str_repeat('v', 64),
        'code_challenge_method' => 'plain',
    ]))->assertStatus(400)->assertJsonPath('error', 'invalid_request');
});

it('still accepts an S256 authorization request', function () {
    $clientId = registerChatGptClient();

    actingAsMcpAdmin();

    $this->get(authorizeQuery($clientId))->assertOk()->assertSee('ChatGPT');
});

it('shows the redirect host on the consent screen so a borrowed client name cannot hide where the code goes', function () {
    $clientId = test()->postJson('/oauth/register', [
        'client_name' => 'Claude',
        'redirect_uris' => ['https://attacker.example/callback'],
    ])->assertSuccessful()->json('client_id');

    actingAsMcpAdmin();

    $this->get(authorizeQuery($clientId, ['redirect_uri' => 'https://attacker.example/callback']))
        ->assertOk()
        ->assertSeeInOrder([trans('mcp::app.authorize.redirects-to'), 'attacker.example']);
});

it('rejects a registration with too many redirect uris', function () {
    $this->postJson('/oauth/register', [
        'client_name' => 'ChatGPT',
        'redirect_uris' => array_map(fn (int $i) => "https://chatgpt.com/callback/{$i}", range(1, 11)),
    ])
        ->assertStatus(400)
        ->assertJsonPath('error', 'invalid_client_metadata')
        ->assertJsonPath('error_description', trans('mcp::app.oauth.registration.too-many-redirect-uris', ['max' => 10]));
});

it('reads the redirect uri cap from config', function () {
    config(['mcp.oauth.registration.max_redirect_uris' => 1]);

    $this->postJson('/oauth/register', [
        'client_name' => 'ChatGPT',
        'redirect_uris' => ['https://chatgpt.com/a', 'https://chatgpt.com/b'],
    ])->assertStatus(400)->assertJsonPath('error', 'invalid_client_metadata');
});

it('rejects a registration with an oversized redirect uri', function () {
    $this->postJson('/oauth/register', [
        'client_name' => 'ChatGPT',
        'redirect_uris' => ['https://chatgpt.com/'.str_repeat('a', 2100)],
    ])
        ->assertStatus(400)
        ->assertJsonPath('error', 'invalid_client_metadata')
        ->assertJsonPath('error_description', trans('mcp::app.oauth.registration.redirect-uri-too-long', ['max' => 2048]));
});

it('rejects an oversized registration body', function () {
    $this->postJson('/oauth/register', [
        'client_name' => 'ChatGPT',
        'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
        'padding' => str_repeat('a', 20_000),
    ])
        ->assertStatus(400)
        ->assertJsonPath('error', 'invalid_client_metadata')
        ->assertJsonPath('error_description', trans('mcp::app.oauth.registration.body-too-large', ['max' => 16384]));
});

it('caps client registration across all ips so a rotating proxy address cannot bypass the per ip limit', function () {
    config([
        'mcp.oauth.registration.rate_limit' => 100,
        'mcp.oauth.registration.global_rate_limit' => 2,
    ]);

    foreach (['203.0.113.1', '203.0.113.2'] as $ip) {
        $this->withServerVariables(['REMOTE_ADDR' => $ip]);

        registerChatGptClient();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.3'])
        ->postJson('/oauth/register', [
            'client_name' => 'ChatGPT',
            'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
        ])
        ->assertStatus(429)
        ->assertJsonPath('error', 'too_many_requests');
});

it('rate limits client registration per ip', function () {
    config(['mcp.oauth.registration.rate_limit' => 2]);

    registerChatGptClient();
    registerChatGptClient();

    $this->postJson('/oauth/register', [
        'client_name' => 'ChatGPT',
        'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
    ])
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('error', 'too_many_requests')
        ->assertJsonPath('error_description', trans('mcp::app.oauth.registration.rate-limited'));
});
