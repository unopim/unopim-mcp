<?php

namespace Webkul\MCP\Providers;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Middleware\AddWwwAuthenticateHeader;
use Laravel\Mcp\Server\Middleware\ReorderJsonAccept;
use Laravel\Passport\Passport;
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

        $this->app->alias(FileManagerInterface::class, FileManager::class);
        $this->app->alias(SkillExecutorInterface::class, SkillExecutor::class);

        $this->configurePassportGuard();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__).'/Resources/lang', 'mcp');

        $this->loadViewsFrom(dirname(__DIR__).'/Resources/views', 'mcp-unopim');

        $this->publishes([
            dirname(__DIR__).'/Resources/views' => resource_path('views/vendor/mcp-unopim'),
        ], 'mcp-unopim-views');

        Mcp::local('unopim-dev', UnoPimAgentServer::class);

        Route::middleware('api')->group(__DIR__.'/../Routes/mcp-routes.php');

        Mcp::oauthRoutes();

        $this->configurePassportViews();

        $this->prioritizeMcpTransportMiddleware();

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

    protected function configurePassportViews(): void
    {
        if (! class_exists(Passport::class)) {
            return;
        }

        Passport::authorizationView('mcp-unopim::authorize');
    }

    protected function prioritizeMcpTransportMiddleware(): void
    {
        $kernel = $this->app->make(HttpKernel::class);

        if (! method_exists($kernel, 'prependToMiddlewarePriority')) {
            return;
        }

        $kernel->prependToMiddlewarePriority(AddWwwAuthenticateHeader::class);
        $kernel->prependToMiddlewarePriority(ReorderJsonAccept::class);
    }

    protected function configurePassportGuard(): void
    {
        if (config('passport.guard', 'web') !== 'web') {
            return;
        }

        if (config('auth.guards.admin')) {
            config(['passport.guard' => 'admin']);
        }
    }

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
