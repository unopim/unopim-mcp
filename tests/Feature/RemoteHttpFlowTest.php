<?php

use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function remoteAdmin(string $type, array $permissions = []): Admin
{
    $role = Role::factory()->create(['permission_type' => $type, 'permissions' => $permissions]);

    return Admin::factory()->create(['role_id' => $role->id]);
}

function mcpRpc(object $test, string $method, array $params = []): array
{
    $response = $test->withHeaders(['Accept' => 'application/json, text/event-stream'])
        ->postJson('/mcp/unopim', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);

    return [$response->status(), json_decode($response->getContent(), true) ?? []];
}

it('renders the consent screen for a signed-in admin', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('E2E client', ['https://claude.ai/api/mcp/auth_callback'], true);

    $this->actingAs(remoteAdmin('all'), 'admin')->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(), 'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
        'response_type' => 'code', 'scope' => '', 'state' => 's',
    ]))->assertOk()->assertSee('E2E client');
});

it('rejects a remote call without a token with 401', function () {
    config(['mcp.api_auth' => true]);

    [$status] = mcpRpc($this, 'tools/list');

    expect($status)->toBe(401);
});

it('lists catalog tools but no developer tools to a remote client', function () {
    Passport::actingAs(remoteAdmin('all'), [], 'api');

    [$status, $body] = mcpRpc($this, 'tools/list');
    $names = collect($body['result']['tools'] ?? [])->pluck('name');

    $cursor = $body['result']['nextCursor'] ?? null;
    while ($cursor) {
        [, $page] = mcpRpc($this, 'tools/list', ['cursor' => $cursor]);
        $names = $names->merge(collect($page['result']['tools'] ?? [])->pluck('name'));
        $cursor = $page['result']['nextCursor'] ?? null;
    }

    expect($status)->toBe(200)
        ->and($names)->toContain('search_products', 'upsert_products', 'get_job_execution')
        ->and($names)->not->toContain('run_database_query', 'dev_tools', 'read_logs');
});

it('runs a catalog tool for a full access admin over http', function () {
    Passport::actingAs(remoteAdmin('all'), [], 'api');

    [$status, $body] = mcpRpc($this, 'tools/call', ['name' => 'search_products', 'arguments' => ['limit' => 2]]);

    expect($status)->toBe(200)->and($body['result']['isError'] ?? false)->toBeFalse();
});

it('refuses a developer tool over http even for a full access admin', function () {
    Passport::actingAs(remoteAdmin('all'), [], 'api');

    [, $body] = mcpRpc($this, 'tools/call', ['name' => 'run_database_query', 'arguments' => ['query' => 'select 1']]);

    expect(json_encode($body))->not->toContain('"isError":false');
});

it('applies the tool permission to a remote catalog-only admin', function () {
    Passport::actingAs(remoteAdmin('custom', ['catalog', 'catalog.products']), [], 'api');

    [, $allowed] = mcpRpc($this, 'tools/call', ['name' => 'search_products', 'arguments' => ['limit' => 1]]);
    [, $denied] = mcpRpc($this, 'tools/call', ['name' => 'upsert_currencies', 'arguments' => ['currencies' => [['code' => 'XTS']]]]);

    expect($allowed['result']['isError'] ?? false)->toBeFalse()
        ->and($denied['result']['content'][0]['text'] ?? '')->toContain('Forbidden');
});

it('refuses a remote call from a disabled admin with a 401 challenge', function () {
    config(['mcp.api_auth' => true]);

    $admin = remoteAdmin('all');
    $admin->update(['status' => 0]);

    Passport::actingAs($admin->fresh(), [], 'api');

    $response = $this->withHeaders(['Accept' => 'application/json, text/event-stream'])
        ->postJson('/mcp/unopim', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'search_products', 'arguments' => ['limit' => 1]]]);

    expect($response->status())->toBe(401)
        ->and($response->headers->get('WWW-Authenticate'))->toContain('Bearer');
});

it('answers a remote admin in the admin ui locale', function () {
    $locale = Locale::where('code', 'de_DE')->first() ?? Locale::factory()->create(['code' => 'de_DE', 'status' => 1]);

    $admin = remoteAdmin('all');
    $admin->update(['ui_locale_id' => $locale->id]);

    Passport::actingAs($admin->fresh(), [], 'api');

    [, $body] = mcpRpc($this, 'tools/call', ['name' => 'get_job_execution', 'arguments' => ['id' => 999999]]);

    expect($body['result']['content'][0]['text'] ?? '')->toContain('Die Jobausführung wurde nicht gefunden.');
});

it('resets the locale to the application default for a remote admin without a ui locale', function () {
    config(['app.locale' => 'en_US']);
    app('translator')->setLocale('de_DE');

    $admin = remoteAdmin('all');
    $admin->update(['ui_locale_id' => null]);

    Passport::actingAs($admin->fresh(), [], 'api');

    [, $body] = mcpRpc($this, 'tools/call', ['name' => 'get_job_execution', 'arguments' => ['id' => 999999]]);

    expect($body['result']['content'][0]['text'] ?? '')->toBe('Job execution not found.');
});
