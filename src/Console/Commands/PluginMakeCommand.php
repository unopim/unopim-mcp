<?php

namespace Webkul\MCP\Console\Commands;

use Illuminate\Console\Command;
use Webkul\MCP\DevTools\PluginGenerator;
use Webkul\MCP\DevTools\TestGenerator;

class PluginMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:make 
                            {action : The action to perform (plugin, test)} 
                            {name? : The name of the plugin or class}
                            {target? : The target class for test generation (if action is test)}
                            {--type=connector : The type of plugin (connector, core-extension, generic)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Advanced UnoPim development: Generate plugins and tests in one command.';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected PluginGenerator $pluginGenerator,
        protected TestGenerator $testGenerator
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'plugin' => $this->makePlugin(),
            'test' => $this->makeTest(),
            default => $this->invalidAction($action),
        };
    }

    /**
     * Generate a new plugin.
     */
    protected function makePlugin(): int
    {
        $name = $this->argument('name');
        $type = $this->option('type');

        if (! $name) {
            $name = $this->ask('What is the name of the plugin?');
        }

        $this->info("Generating {$type} plugin: {$name}...");

        try {
            $this->pluginGenerator->generate($name, $type);
            $this->info("Plugin {$name} generated successfully!");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate plugin: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Generate a test for a class.
     */
    protected function makeTest(): int
    {
        $plugin = $this->argument('name');
        $class = $this->argument('target');

        if (! $plugin) {
            $plugin = $this->ask('Which plugin is this for?');
        }

        if (! $class) {
            $class = $this->ask('Which class should be tested? (e.g. Services/MyService)');
        }

        $this->info("Generating test for {$class} in {$plugin}...");

        try {
            $path = $this->testGenerator->generate($plugin, $class);
            $this->info("Test generated at: {$path}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate test: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Handle invalid action.
     */
    protected function invalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}. Supported actions: plugin, test.");

        return Command::FAILURE;
    }
}
