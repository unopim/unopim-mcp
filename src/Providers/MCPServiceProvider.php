<?php

namespace Webkul\MCP\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Webkul\MCP\Console\Commands\DevMcpCommand;
use Webkul\MCP\Console\Commands\MCPInspectorCommand;
use Webkul\MCP\Console\Commands\PluginMakeCommand;
use Webkul\MCP\Console\Commands\SetupCommand;
use Webkul\MCP\Contracts\FileManagerInterface;
use Webkul\MCP\Contracts\SkillExecutorInterface;
use Webkul\MCP\DevTools\CommandRunner;
use Webkul\MCP\DevTools\FileManager;
use Webkul\MCP\DevTools\PluginGenerator;
use Webkul\MCP\DevTools\TestGenerator;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Services\SkillExecutor;
use Webkul\MCP\Services\SkillLoader;
use Webkul\MCP\Services\SkillParser;

class MCPServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/mcp.php', 'mcp'
        );

        $this->publishes([
            dirname(__DIR__).'/Config/mcp.php' => config_path('mcp.php'),
        ], 'mcp-config');

        $this->app->singleton(SkillParser::class, function () {
            return new SkillParser;
        });

        $this->app->singleton(SkillLoader::class, function ($app) {
            return new SkillLoader($app->make(SkillParser::class));
        });

        $this->app->singleton(FileManagerInterface::class, function () {
            return new FileManager;
        });

        $this->app->singleton(CommandRunner::class, function () {
            return new CommandRunner;
        });

        $this->app->singleton(PluginGenerator::class, function ($app) {
            return new PluginGenerator($app->make(FileManagerInterface::class));
        });

        $this->app->singleton(TestGenerator::class, function ($app) {
            return new TestGenerator($app->make(FileManagerInterface::class));
        });

        $this->app->singleton(SkillExecutorInterface::class, function ($app) {
            return new SkillExecutor(
                $app->make(FileManagerInterface::class),
                $app->make(CommandRunner::class),
                $app->make(PluginGenerator::class),
                $app->make(TestGenerator::class)
            );
        });

        // Aliases for backward compatibility if needed, though interfaces are preferred
        $this->app->alias(FileManagerInterface::class, FileManager::class);
        $this->app->alias(SkillExecutorInterface::class, SkillExecutor::class);

        $this->configurePassportGuard();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Mcp::local('unopim-dev', UnoPimAgentServer::class);

        $middlewares = ['api'];

        if (config('mcp.api_auth')) {
            $middlewares[] = 'auth:api';
        }

        Route::middleware($middlewares)->group(__DIR__.'/../Routes/mcp-routes.php');

        Mcp::oauthRoutes();

        $this->registerLoginRouteFallback();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MCPInspectorCommand::class,
                SetupCommand::class,
                DevMcpCommand::class,
                PluginMakeCommand::class,
            ]);
        }
    }

    /**
     * Passport defaults to the "web" guard, which UnoPim does not define.
     * Admin sessions live on the "admin" guard, so the OAuth authorize
     * flow must check that guard to recognize logged-in admins.
     */
    protected function configurePassportGuard(): void
    {
        $guard = config('passport.guard', 'web');

        if (! config("auth.guards.{$guard}") && config('auth.guards.admin')) {
            config(['passport.guard' => 'admin']);
        }
    }

    /**
     * Passport's authorize flow redirects guests to the framework-default
     * "login" route, which UnoPim does not define — alias it to admin login.
     */
    protected function registerLoginRouteFallback(): void
    {
        $this->app->booted(function () {
            if (Route::has('login') || ! Route::has('admin.session.create')) {
                return;
            }

            Route::middleware('web')
                ->get('login', fn () => redirect()->route('admin.session.create'))
                ->name('login');
        });
    }
}
