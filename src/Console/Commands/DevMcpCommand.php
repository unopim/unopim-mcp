<?php

namespace Webkul\MCP\Console\Commands;

use Illuminate\Console\Command;

class DevMcpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:dev';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the UnoPim Dev MCP server (alias for mcp:start unopim-dev)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return $this->call('mcp:start', ['handle' => 'unopim-dev']);
    }
}
