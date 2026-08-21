<?php

namespace Webkul\MCP\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class MCPInspectorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:inspector {server : The name of the server to test (e.g., unopim-dev, mcp/dev, mcp/unopim)} {--host=127.0.0.1 : The host IP to bind the proxy to} {--port=6277 : The port to run the inspector on} {--no-auth : Disable the session token requirement}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the MCP Inspector to test an MCP server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $server = $this->argument('server');
        $host = $this->option('host');
        $port = $this->option('port');
        $noAuth = $this->option('no-auth');

        $this->info("Launching MCP Inspector for '{$server}' on {$host}:{$port}...");

        if ($noAuth) {
            $this->warn('Note: Authentication is disabled via --no-auth.');
        }

        // Distinguish HTTP vs STDIO based on the presence of a slash
        if (str_contains($server, '/')) {
            // It's an HTTP endpoint (e.g., mcp/dev -> http://localhost/api/mcp/dev/sse)
            // Note: the inspector requires an SSE endpoint URL for HTTP testing
            $url = rtrim(config('app.url'), '/').'/api/'.ltrim($server, '/');

            $command = 'npx -y @modelcontextprotocol/inspector';
            $this->info('To test your HTTP endpoint via the Inspector UI:');
            $this->info("1. Select the 'SSE' or 'StreamableHttp' tab in the browser.");
            $this->info("2. For SSE enter: {$url}/sse (or just {$url} depending on your config)");
            $this->info('3. If auth is enabled, ensure you pass the correct Bearer token.');
        } else {
            // It's a STDIO agent (e.g., unopim-dev)
            $command = [
                'npx',
                '-y',
                '@modelcontextprotocol/inspector',
                PHP_BINARY,
                base_path('artisan'),
                'mcp:start',
                $server,
            ];

            $this->info('Connecting via STDIO invoking: '.PHP_BINARY.' '.base_path('artisan')." mcp:start {$server}");
        }

        // We use Symfony Process to execute the node script with TTY enabled.
        // This allows us to intercept the terminal output and replace 'localhost' with the requested host IP.
        $process = is_array($command) ? new Process($command) : Process::fromShellCommandline($command);
        $process->setTimeout(null);

        $env = [
            'HOST' => $host,
            'PORT' => $port,
            'ALLOWED_ORIGINS' => "http://localhost:6274,http://127.0.0.1:6274,http://{$host}:6274",
            'NODE_TLS_REJECT_UNAUTHORIZED' => '0',
        ];

        if ($noAuth) {
            $env['DANGEROUSLY_OMIT_AUTH'] = 'true';
        }

        $process->setEnv(array_merge($_SERVER, $_ENV, $env));

        if (Process::isTtySupported()) {
            $process->setTty(true);
        }

        $this->info("Starting Inspector... If you use a Cloud IDE, try --host=0.0.0.0 to fix 'Invalid origin' errors.");

        $process->run(function ($type, $buffer) use ($host, $port) {
            // Intercept the stream and rewrite localhost to the actual IP requested.
            // This ensures tokens and proxy URLs are clickable in the terminal.
            $buffer = str_replace('localhost', $host, $buffer);

            if ($port != 6277) {
                $buffer = str_replace(':6277', ":{$port}", $buffer);
            }

            $this->output->write($buffer);
        });

        return Command::SUCCESS;
    }
}
