<?php

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

dataset('authorization server metadata urls', [
    'root' => ['/.well-known/oauth-authorization-server'],
    'path suffixed' => ['/.well-known/oauth-authorization-server/mcp/unopim'],
]);

it('advertises the token endpoint auth methods the server accepts', function (string $url) {
    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('token_endpoint_auth_methods_supported', ['none'])
        ->assertJsonPath('code_challenge_methods_supported', ['S256']);
})->with('authorization server metadata urls');

it('keeps the metadata laravel mcp publishes', function (string $url) {
    $this->getJson($url)
        ->assertOk()
        ->assertJsonStructure([
            'issuer', 'authorization_endpoint', 'token_endpoint', 'registration_endpoint',
            'response_types_supported', 'scopes_supported', 'grant_types_supported',
        ]);
})->with('authorization server metadata urls');

it('uses the same issuer in both metadata documents', function () {
    $issuer = $this->getJson('/.well-known/oauth-authorization-server')->json('issuer');

    $this->getJson('/.well-known/oauth-protected-resource/mcp/unopim')
        ->assertOk()
        ->assertJsonPath('authorization_servers', [$issuer])
        ->assertJsonPath('resource', url('/mcp/unopim'));
});

it('registers a chatgpt client as a public client', function () {
    $this->postJson('/oauth/register', [
        'client_name' => 'ChatGPT',
        'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
    ])
        ->assertSuccessful()
        ->assertJsonPath('token_endpoint_auth_method', 'none')
        ->assertJsonPath('redirect_uris', ['https://chatgpt.com/connector_platform_oauth_redirect']);
});

it('accepts the resource parameter chatgpt sends on authorize', function () {
    $clientId = $this->postJson('/oauth/register', [
        'client_name' => 'ChatGPT',
        'redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect'],
    ])->json('client_id');

    $admin = Admin::factory()->create([
        'role_id' => Role::factory()->create(['permission_type' => 'all'])->id,
    ]);

    $this->actingAs($admin, 'admin')->get('/oauth/authorize?'.http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => 'https://chatgpt.com/connector_platform_oauth_redirect',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'state',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 64), true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
        'resource' => url('/mcp/unopim'),
    ]))->assertOk()->assertSee('ChatGPT');
});
