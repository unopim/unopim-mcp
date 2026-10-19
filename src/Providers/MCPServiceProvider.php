<?php

namespace Webkul\MCP\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Middleware\AddWwwAuthenticateHeader;
use Laravel\Mcp\Server\Middleware\ReorderJsonAccept;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
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
use Webkul\MCP\Http\Middleware\AdvertiseTokenEndpointAuthMethods;
use Webkul\MCP\Http\Middleware\LimitClientRegistration;
use Webkul\MCP\Http\Middleware\RequireS256CodeChallenge;
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
                $app->make(TestGenerator::class),
                $app->make(SkillLoader::class),
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

        $this->advertiseTokenEndpointAuthMethods();

        $this->guardClientRegistration();

        $this->requireS256CodeChallenge();

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

    protected function advertiseTokenEndpointAuthMethods(): void
    {
        foreach (Route::getRoutes()->get('GET') as $route) {
            if (str_starts_with($route->uri(), '.well-known/oauth-authorization-server')) {
                $route->middleware(AdvertiseTokenEndpointAuthMethods::class);
            }
        }
    }

    /**
     * Throttle and cap the dynamic client registration endpoint laravel/mcp registers unguarded.
     *
     * The hourly global cap holds even when a proxy hides the client IP and every request appears to come from a different address.
     */
    protected function guardClientRegistration(): void
    {
        $rejection = fn (Request $request, array $headers) => response()->json([
            'error' => 'too_many_requests',
            'error_description' => trans('mcp::app.oauth.registration.rate-limited'),
        ], 429, $headers);

        RateLimiter::for('mcp-oauth-register', fn (Request $request) => [
            Limit::perMinute((int) config('mcp.oauth.registration.rate_limit', 10))
                ->by($request->ip())
                ->response($rejection),
            Limit::perHour((int) config('mcp.oauth.registration.global_rate_limit', 100))
                ->by('global')
                ->response($rejection),
        ]);

        foreach (Route::getRoutes()->get('POST') as $route) {
            if ($route->uri() === 'oauth/register') {
                $route->middleware(['throttle:mcp-oauth-register', LimitClientRegistration::class]);
            }
        }
    }

    /**
     * Passport registers its routes from its own provider, so the authorize route is guarded once the app has booted.
     */
    protected function requireS256CodeChallenge(): void
    {
        $this->app->booted(function (): void {
            Route::getRoutes()->getByName('passport.authorizations.authorize')?->middleware(RequireS256CodeChallenge::class);
        });
    }

    protected function configurePassportViews(): void
    {
        if (! class_exists(Passport::class) || $this->app->bound(AuthorizationViewResponse::class)) {
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
