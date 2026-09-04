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

    /**
     * Passport ships no consent screen and binds no default implementation of
     * AuthorizationViewResponse — the contract is only bound when the app calls
     * Passport::authorizationView(). Without this, /oauth/authorize dies with
     * "Target [...AuthorizationViewResponse] is not instantiable" and the
     * remote connector flow cannot complete.
     *
     * laravel/mcp ships a consent screen under the "mcp" view namespace. A copy
     * published via the `mcp-views` tag lands in resources/views/mcp, so prefer
     * that when present to keep the screen restylable.
     */
    protected function configurePassportViews(): void
    {
        if (! class_exists(Passport::class)) {
            return;
        }

        // laravel/mcp's stock screen points @vite at resources/css/app.css, which
        // is empty in UnoPim — the page renders unstyled and its dark-mode script
        // paints it black. Ship a self-contained, UnoPim-branded screen instead.
        // loadViewsFrom() already resolves resources/views/vendor/mcp-unopim
        // first, so publishing the view is all it takes to restyle this.
        Passport::authorizationView('mcp-unopim::authorize');
    }

    /**
     * Laravel sorts route middleware by the kernel's priority list, and
     * Authenticate (auth:api) is on it while laravel/mcp's transport helpers are
     * not — so auth:api gets hoisted to the front of the MCP route and runs
     * before them, regardless of the order the route declares.
     *
     * That breaks remote clients in two ways. ReorderJsonAccept never gets to
     * move `application/json` to the front of the Accept header, so
     * expectsJson() is false for a `text/event-stream, application/json`
     * request and Authenticate answers with a 302 to the admin login instead of
     * a 401. AddWwwAuthenticateHeader then only ever sees that redirect, and it
     * decorates 401s only, so the Bearer challenge that tells a client where to
     * re-authorize is never sent. A connector handed an HTML login page has
     * nothing to act on and drops the connection.
     *
     * Promoting both above Authenticate restores the intended order.
     */
    protected function prioritizeMcpTransportMiddleware(): void
    {
        $kernel = $this->app->make(HttpKernel::class);

        if (! method_exists($kernel, 'prependToMiddlewarePriority')) {
            return;
        }

        $kernel->prependToMiddlewarePriority(AddWwwAuthenticateHeader::class);
        $kernel->prependToMiddlewarePriority(ReorderJsonAccept::class);
    }

    /**
     * UnoPim admin sessions live on the "admin" guard. Passport defaults to
     * "web", which UnoPim *does* define (session driver, "users" provider) but
     * never authenticates against — the admin login only ever populates the
     * "admin" guard, and a session guard stores its user under a per-guard key.
     *
     * Leaving Passport on "web" makes /oauth/authorize treat a signed-in admin
     * as a guest and bounce them to the login page. The admin login then sees
     * them as already authenticated and forwards them to the dashboard, so the
     * OAuth flow is abandoned and the connector never gets its code. Logging
     * out first only turns that into a loop: the login populates "admin",
     * Passport re-checks "web", and bounces again.
     *
     * The earlier check tested whether the configured guard was *missing*,
     * which is never true here, so the switch never happened. Override only the
     * framework default so an explicit passport.guard choice is respected.
     */
    protected function configurePassportGuard(): void
    {
        if (config('passport.guard', 'web') !== 'web') {
            return;
        }

        if (config('auth.guards.admin')) {
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
