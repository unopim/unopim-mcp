<?php

namespace Webkul\MCP\Tests;

use Illuminate\Support\Facades\Config;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Tool;
use Tests\TestCase;

class MCPTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable MCP API auth by default for all MCP tests.
        // Feature tests like SecurityTest will explicitly re-enable it if needed.
        Config::set('mcp.api_auth', false);
    }

    /**
     * Product values that set the channel and locale scoped `name` attribute.
     */
    protected function productNameValues(string $name): array
    {
        $channel = core()->getDefaultChannel();

        return [
            'channel_locale_specific' => [
                $channel->code => [$channel->locales->first()->code => ['name' => $name]],
            ],
        ];
    }

    /**
     * Run a tool directly and decode its JSON payload.
     *
     * @param  class-string<Tool>  $tool
     * @return array<string, mixed>
     */
    protected function toolPayload(string $tool, array $arguments = []): array
    {
        $response = app($tool)->handle(new Request($arguments));

        return json_decode((string) $response->content(), true) ?? [];
    }
}
