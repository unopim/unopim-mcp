<?php

namespace Webkul\MCP\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Throwable;

class SetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install MCP OAuth, CORS, and plugin auth wiring for UnoPim MCP package.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting UnoPim MCP Setup...');

        if (! class_exists(Passport::class)) {
            $this->warn('Laravel Passport is not installed. Skipping OAuth scaffolding.');
        } else {
            if (! Schema::hasTable('oauth_auth_codes')) {
                $this->info('Ensuring Passport installation scaffolding...');

                $this->call('passport:install', [
                    '--no-interaction' => true,
                ]);
            }
        }

        $this->info('Publishing UnoPim MCP configuration...');
        $this->call('vendor:publish', [
            '--provider' => 'Webkul\MCP\Providers\MCPServiceProvider',
            '--tag' => 'mcp-config',
            '--force' => true,
        ]);

        $this->line('Clearing caches...');
        try {
            $this->call('optimize:clear');
        } catch (Throwable) {
            $this->warn('optimize:clear failed. Falling back to config:clear and route:clear.');
            $this->call('config:clear');
            $this->call('route:clear');
        }

        $this->newLine();
        $this->info('UnoPim MCP setup completed.');

        return self::SUCCESS;
    }
}
