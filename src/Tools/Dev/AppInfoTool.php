<?php

namespace Webkul\MCP\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\MCP\Tools\BaseMcpTool;

class AppInfoTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Retrieve technical information about the UnoPim application, including versions and installed packages.';

    /**
     * The tool's name.
     */
    public string $name = 'get_app_info';

    protected function execute(Request $request): Response
    {
        $packages = [];
        $packagesPath = base_path('packages/Webkul');

        if (is_dir($packagesPath)) {
            foreach (scandir($packagesPath) as $dir) {
                if ($dir === '.' || $dir === '..' || ! is_dir($packagesPath.'/'.$dir)) {
                    continue;
                }

                $composerJson = $packagesPath.'/'.$dir.'/composer.json';
                if (file_exists($composerJson)) {
                    $content = json_decode(file_get_contents($composerJson), true);
                    $packages[] = [
                        'name' => $content['name'] ?? $dir,
                        'version' => $content['version'] ?? 'dev-main',
                    ];
                }
            }
        }

        return Response::json([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'unopim_packages' => $packages,
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
