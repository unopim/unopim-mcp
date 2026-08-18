<?php

namespace Webkul\MCP\DevTools;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginGenerator
{
    public function __construct(
        protected FileManager $fileManager
    ) {}

    /**
     * Generate a full UnoPim plugin structure.
     *
     * @return array{name: string, path: string, message: string, files: list<string>}
     */
    public function generate(string $name, string $type = 'connector'): array
    {
        $name = Str::studly($name);
        $basePath = "packages/Webkul/{$name}";

        if (File::exists(base_path($basePath))) {
            throw new \RuntimeException("Plugin [{$name}] already exists at: {$basePath}");
        }

        $files = $this->buildStubs($name, $type);
        $created = [];

        foreach ($files as $relativePath => $content) {
            $this->fileManager->create("{$basePath}/{$relativePath}", $content);
            $created[] = "{$basePath}/{$relativePath}";
        }

        return [
            'name' => $name,
            'path' => $basePath,
            'message' => "Plugin [{$name}] ({$type}) generated successfully.",
            'files' => $created,
        ];
    }

    /**
     * Build all stub files for the plugin based on type.
     *
     * @return array<string, string>
     */
    protected function buildStubs(string $name, string $type): array
    {
        $lower = Str::lower($name);
        $kebab = Str::kebab($name);
        $snake = Str::snake($name);

        return match ($type) {
            'connector' => $this->buildConnectorStubs($name, $lower, $kebab, $snake),
            'core-extension' => $this->buildCoreExtensionStubs($name, $lower, $kebab, $snake),
            'generic' => $this->buildGenericStubs($name, $lower, $kebab, $snake),
            default => throw new \InvalidArgumentException("Unsupported plugin type: {$type}"),
        };
    }

    protected function buildConnectorStubs(string $name, string $lower, string $kebab, string $snake): array
    {
        return [
            // Root
            'composer.json' => $this->stubComposer($name, $lower, "UnoPim {$name} Connector Plugin"),

            // Config
            'src/Config/acl.php' => $this->stubAcl($name, $kebab),
            'src/Config/menu.php' => $this->stubMenu($name, $kebab),
            'src/Config/importers.php' => "<?php\n\nreturn [];\n",
            'src/Config/exporters.php' => "<?php\n\nreturn [];\n",
            'src/Config/quick_exporters.php' => "<?php\n\nreturn [];\n",

            // Contracts
            'src/Contracts/Credential.php' => $this->stubCredentialContract($name),

            // Providers
            "src/Providers/{$name}ServiceProvider.php" => $this->stubServiceProvider($name, $snake, $kebab, true),
            'src/Providers/ModuleServiceProvider.php' => $this->stubModuleServiceProvider($name),

            // Models
            'src/Models/Credential.php' => $this->stubCredentialModel($name, $snake),
            'src/Models/CredentialProxy.php' => $this->stubCredentialProxy($name),

            // Repositories
            'src/Repositories/CredentialRepository.php' => $this->stubCredentialRepository($name),

            // DataGrids
            'src/DataGrids/Settings/CredentialDataGrid.php' => $this->stubCredentialDataGrid($name, $snake, $kebab),

            // Controller
            'src/Http/Controllers/CredentialController.php' => $this->stubCredentialController($name, $kebab),

            // Routes
            "src/Routes/{$kebab}-routes.php" => $this->stubRoutes($name, $kebab),

            // Console Commands
            "src/Console/Commands/{$name}Installer.php" => $this->stubInstaller($name, $kebab),

            // Views
            'src/Resources/views/credentials/index.blade.php' => $this->stubCredentialIndexView($name, $snake, $kebab),

            // Lang
            'src/Resources/lang/en/app.php' => "<?php\n\nreturn ['credentials' => ['title' => 'Credentials']];\n",

            // Migrations
            'src/Database/Migration/'.date('Y_m_d_His')."_create_wk_{$snake}_credentials_table.php" => $this->stubMigration($name, $snake),
        ];
    }

    protected function buildCoreExtensionStubs(string $name, string $lower, string $kebab, string $snake): array
    {
        return [
            'composer.json' => $this->stubComposer($name, $lower, "UnoPim {$name} Core Extension"),
            "src/Providers/{$name}ServiceProvider.php" => $this->stubServiceProvider($name, $snake, $kebab, false),
            'src/Resources/lang/en/app.php' => "<?php\n\nreturn [];\n",
            'src/Config/acl.php' => "<?php\n\nreturn [];\n",
            'src/Config/menu.php' => "<?php\n\nreturn [];\n",
        ];
    }

    protected function buildGenericStubs(string $name, string $lower, string $kebab, string $snake): array
    {
        return [
            'composer.json' => $this->stubComposer($name, $lower, "UnoPim {$name} Package"),
            "src/Providers/{$name}ServiceProvider.php" => $this->stubServiceProvider($name, $snake, $kebab, false, false),
        ];
    }

    // ─── Stubs ────────────────────────────────────────────────────────────────

    protected function stubComposer(string $name, string $lower, string $description): string
    {
        return json_encode([
            'name' => "webkul/{$lower}",
            'description' => $description,
            'type' => 'laravel-library',
            'require' => ['php' => '^8.2'],
            'autoload' => [
                'psr-4' => ["Webkul\\{$name}\\" => 'src/'],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        "Webkul\\{$name}\\Providers\\{$name}ServiceProvider",
                    ],
                ],
                'concord' => [
                    'modules' => [
                        "Webkul\\{$name}\\Providers\\ModuleServiceProvider",
                    ],
                ],
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function stubAcl(string $name, string $kebab): string
    {
        return <<<PHP
<?php

return [
    ['key' => '{$kebab}', 'name' => '{$name}', 'route' => 'admin.{$kebab}.credentials.index', 'sort' => 1],
    ['key' => '{$kebab}.credentials', 'name' => 'Credentials', 'route' => 'admin.{$kebab}.credentials.index', 'sort' => 1],
];
PHP;
    }

    protected function stubMenu(string $name, string $kebab): string
    {
        return <<<PHP
<?php

return [
    [
        'key'        => '{$kebab}',
        'name'       => '{$name}',
        'route'      => 'admin.{$kebab}.credentials.index',
        'sort'       => 99,
        'icon'       => 'icon-settings',
    ],
    [
        'key'        => '{$kebab}.credentials',
        'name'       => 'Credentials',
        'route'      => 'admin.{$kebab}.credentials.index',
        'sort'       => 1,
    ],
];
PHP;
    }

    protected function stubCredentialContract(string $name): string
    {
        return <<<PHP
<?php

namespace Webkul\\{$name}\\Contracts;

interface Credential
{
    //
}
PHP;
    }

    protected function stubServiceProvider(string $name, string $snake, string $kebab, bool $isConnector = true, bool $hasConfig = true): string
    {
        $configs = '';
        if ($hasConfig) {
            $configs .= "        \$this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');\n";
            $configs .= "        \$this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');\n";
            if ($isConnector) {
                $configs .= "        \$this->mergeConfigFrom(__DIR__.'/../Config/importers.php', 'importers');\n";
                $configs .= "        \$this->mergeConfigFrom(__DIR__.'/../Config/exporters.php', 'exporters');\n";
                $configs .= "        \$this->mergeConfigFrom(__DIR__.'/../Config/quick_exporters.php', 'quick_exporters');\n";
            }
        }

        $moduleProvider = $isConnector ? "        \$this->app->register(ModuleServiceProvider::class);\n" : '';

        $installer = '';
        if ($isConnector) {
            $installer = <<<PHP
        if (\$this->app->runningInConsole()) {
            \$this->commands([
                \Webkul\\{$name}\\Console\\Commands\\{$name}Installer::class,
            ]);
        }
PHP;
        }

        $routes = $hasConfig ? "        Route::middleware('admin')->group(__DIR__ . '/../Routes/{$kebab}-routes.php');" : '';

        return <<<PHP
<?php

namespace Webkul\\{$name}\\Providers;

use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Support\\Facades\\Event;
use Illuminate\\Support\\Facades\\Route;

class {$name}ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
{$configs}
{$moduleProvider}
{$installer}
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        \$this->loadMigrationsFrom(__DIR__.'/../Database/Migration');
        \$this->loadTranslationsFrom(__DIR__.'/../Resources/lang', '{$kebab}');
        \$this->loadViewsFrom(__DIR__.'/../Resources/views', '{$kebab}');

{$routes}

        Event::listen('unopim.admin.layout.head.before', function() {
            // Add custom head assets here
        });
    }
}
PHP;
    }

    protected function stubModuleServiceProvider(string $name): string
    {
        return <<<PHP
<?php

namespace Webkul\\{$name}\\Providers;

use Webkul\\Core\\Providers\\CoreModuleServiceProvider;
use Webkul\\{$name}\\Models\\Credential;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected \$models = [
        Credential::class,
    ];
}
PHP;
    }

    protected function stubCredentialModel(string $name, string $snake): string
    {
        return <<<PHP
<?php

namespace Webkul\\{$name}\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Webkul\\{$name}\\Contracts\\Credential as CredentialContract;
use Webkul\\HistoryControl\\Traits\\HistoryTrait;
use Webkul\\HistoryControl\\Interfaces\\PresentableHistoryInterface;

class Credential extends Model implements CredentialContract, PresentableHistoryInterface
{
    use HistoryTrait;

    /**
     * The table associated with the model.
     */
    protected \$table = 'wk_{$snake}_credentials';

    /**
     * The attributes that are mass assignable.
     */
    protected \$fillable = [
        'name',
        'status',
        'api_url',
        'api_key',
        'extras',
    ];

    /**
     * The attributes that should be cast.
     */
    protected \$casts = [
        'extras' => 'array',
        'status' => 'boolean',
    ];

    /**
     * Fields to exclude from audit trail.
     */
    protected \$auditExclude = [
        'api_key',
    ];
}
PHP;
    }

    protected function stubCredentialProxy(string $name): string
    {
        return <<<PHP
<?php

namespace Webkul\\{$name}\\Models;

use Konekt\\Concord\\Proxies\\ModelProxy;

class CredentialProxy extends ModelProxy
{
    //
}
PHP;
    }

    protected function stubCredentialRepository(string $name): string
    {
        return <<<PHP
<?php

namespace Webkul\\{$name}\\Repositories;

use Webkul\\Core\\Eloquent\\Repository;

class CredentialRepository extends Repository
{
    /**
     * The model class.
     */
    public function model(): string
    {
        return 'Webkul\\\\{$name}\\\\Contracts\\\\Credential';
    }
}
PHP;
    }

    protected function stubCredentialController(string $name, string $kebab): string
    {
        return <<<PHP
<?php

namespace Webkul\\{$name}\\Http\\Controllers;

use Illuminate\\Http\\JsonResponse;
use Illuminate\\Routing\\Controller;
use Webkul\\{$name}\\Repositories\\CredentialRepository;

class CredentialController extends Controller
{
    public function __construct(
        protected CredentialRepository \$credentialRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(\Webkul\\{$name}\\DataGrids\\Settings\\CredentialDataGrid::class)->toJson();
        }

        return view('{$kebab}::credentials.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): JsonResponse
    {
        // Validation would be done via a FormRequest in a real scenario.
        \$data = request()->all();
        \$credential = \$this->credentialRepository->create(\$data);

        return new JsonResponse([
            'redirect_url' => route('admin.{$kebab}.credentials.index'),
            'message'      => 'Credential created successfully.',
            'data'         => \$credential,
        ]);
    }

    /**
     * Test connection to the API.
     */
    public function testConnection(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Connection successful.',
        ]);
    }
}
PHP;
    }

    protected function stubRoutes(string $name, string $kebab): string
    {
        return <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
use Webkul\\{$name}\\Http\\Controllers\\CredentialController;

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_path')], function () {
    Route::prefix('{$kebab}')->name('admin.{$kebab}.')->group(function () {
        Route::get('credentials', [CredentialController::class, 'index'])->name('credentials.index');
        Route::post('credentials', [CredentialController::class, 'store'])->name('credentials.store');
        Route::post('credentials/test-connection', [CredentialController::class, 'testConnection'])->name('credentials.test-connection');
    });
});
PHP;
    }

    protected function stubCredentialDataGrid(string $name, string $snake, string $kebab): string
    {
        return <<<PHP
<?php

namespace Webkul\\{$name}\\DataGrids\\Settings;

use Illuminate\\Support\\Facades\\DB;
use Webkul\\DataGrid\\DataGrid;

class CredentialDataGrid extends DataGrid
{
    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        return DB::table('wk_{$snake}_credentials')
            ->select('id', 'name', 'status', 'api_url');
    }

    public function prepareColumns()
    {
        \$this->addColumn([
            'index'      => 'id',
            'label'      => 'ID',
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
        ]);

        \$this->addColumn([
            'index'      => 'name',
            'label'      => 'Name',
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
        ]);

        \$this->addColumn([
            'index'      => 'status',
            'label'      => 'Status',
            'type'       => 'boolean',
            'closure'    => fn (\$row) => \$row->status ? '<span class="label-active">Active</span>' : '<span class="label-info">Inactive</span>',
        ]);
    }

    public function prepareActions()
    {
        \$this->addAction([
            'icon'   => 'icon-edit',
            'title'  => 'Edit',
            'method' => 'GET',
            'url'    => function (\$row) {
                return route('admin.{$kebab}.credentials.edit', \$row->id);
            },
        ]);
    }
}
PHP;
    }

    protected function stubMigration(string $name, string $snake): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wk_{$snake}_credentials', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->boolean('status')->default(0);
            \$table->string('api_url')->nullable();
            \$table->text('api_key')->nullable();
            \$table->json('extras')->nullable();
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wk_{$snake}_credentials');
    }
};
PHP;
    }

    protected function stubInstaller(string $name, string $kebab): string
    {
        return <<<PHP
<?php

namespace Webkul\\{$name}\\Console\\Commands;

use Illuminate\\Console\\Command;

class {$name}Installer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected \$signature = '{$kebab}:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected \$description = 'Install the {$name} connector package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \$this->info('Installing UnoPim {$name} connector...');

        if (\$this->confirm('Would you like to run the migrations now?', true)) {
            \$this->call('migrate');
        }

        \$this->info('Publishing configuration files...');
        
        \$this->call('vendor:publish', [
            '--provider' => 'Webkul\\\\{$name}\\\\Providers\\\\{$name}ServiceProvider',
            '--tag'      => '{$kebab}-config',
        ]);

        \$this->info('UnoPim {$name} connector installed successfully!');
    }
}
PHP;
    }

    protected function stubCredentialIndexView(string $name, string $snake, string $kebab): string
    {
        return <<<BLADE
<x-admin::layouts>
    <x-slot:title>
        {{ __('{$kebab}::app.credentials.title') }}
    </x-slot:title>

    <v-{$kebab}-credentials></v-{$kebab}-credentials>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-{$kebab}-credentials-template">
            <div>
                <div class="flex justify-between items-center">
                    <p class="text-[20px] text-gray-800 dark:text-white font-bold">
                        {{ __('{$kebab}::app.credentials.title') }}
                    </p>

                    <div class="flex gap-x-[10px] items-center">
                        <button
                            type="button"
                            class="primary-button"
                            @click="\$refs.credentialCreateModal.open()"
                        >
                            {{ __('{$kebab}::app.credentials.create-btn') }}
                        </button>
                    </div>
                </div>

                <x-admin::datagrid :src="route('admin.{$kebab}.credentials.index')" />

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                    ref="credentialCreateForm"
                >
                    <form @submit="handleSubmit(\$event, create)">
                        <x-admin::modal ref="credentialCreateModal">
                            <x-slot:header>
                                <p class="text-[18px] text-gray-800 dark:text-white font-bold">
                                    {{ __('{$kebab}::app.credentials.create-title') }}
                                </p>
                            </x-slot:header>

                            <x-slot:content>
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ __('{$kebab}::app.credentials.name') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="name"
                                        rules="required"
                                        :label="__('{$kebab}::app.credentials.name')"
                                        :placeholder="__('{$kebab}::app.credentials.name')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ __('{$kebab}::app.credentials.apiUrl') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="api_url"
                                        rules="required|url"
                                        :label="__('{$kebab}::app.credentials.apiUrl')"
                                        :placeholder="__('{$kebab}::app.credentials.apiUrl')"
                                    />

                                    <x-admin::form.control-group.error control-name="api_url" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ __('{$kebab}::app.credentials.apiKey') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="password"
                                        name="api_key"
                                        rules="required"
                                        :label="__('{$kebab}::app.credentials.apiKey')"
                                        :placeholder="__('{$kebab}::app.credentials.apiKey')"
                                    />

                                    <x-admin::form.control-group.error control-name="api_key" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="flex items-center gap-[10px]">
                                    <x-admin::form.control-group.control
                                        type="switch"
                                        name="status"
                                        :value="1"
                                        :checked="true"
                                    />

                                    <x-admin::form.control-group.label>
                                        {{ __('{$kebab}::app.credentials.status') }}
                                    </x-admin::form.control-group.label>
                                </x-admin::form.control-group>
                            </x-slot:content>

                            <x-slot:footer>
                                <button type="submit" class="primary-button">
                                    {{ __('{$kebab}::app.credentials.save-btn') }}
                                </button>
                            </x-slot:footer>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-{$kebab}-credentials', {
                template: '#v-{$kebab}-credentials-template',

                methods: {
                    create(params, { setErrors }) {
                        const formData = new FormData(this.\$refs.credentialCreateForm);
                        const apiUrl = formData.get('api_url');
                        const apiKey = formData.get('api_key');

                        if (!apiUrl || !apiKey) {
                            return;
                        }

                        this.\$axios.post("{{ route('admin.{$kebab}.credentials.test-connection') }}", {
                            api_url: apiUrl,
                            api_key: apiKey
                        })
                        .then(response => {
                            this.submitForm(formData, setErrors);
                        })
                        .catch(error => {
                            window.alert(error.response.data.message || 'Connection failed.');
                        });
                    },

                    submitForm(formData, setErrors) {
                        this.\$axios.post("{{ route('admin.{$kebab}.credentials.store') }}", formData)
                            .then(response => {
                                window.location.href = response.data.redirect_url;
                            })
                            .catch(error => {
                                if (error.response.status === 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
BLADE;
    }
}
