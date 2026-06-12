<?php

use Laravel\Mcp\Facades\Mcp;
use Webkul\MCP\Http\Middleware\AuthenticateAdminFromApiGuard;
use Webkul\MCP\Servers\UnoPimAgentServer;

Mcp::web('mcp/unopim', UnoPimAgentServer::class)
    ->middleware(AuthenticateAdminFromApiGuard::class);
