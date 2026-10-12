<?php

namespace Webkul\MCP\Tests;

use Illuminate\Support\Facades\Config;
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
}
