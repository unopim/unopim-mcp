<?php

namespace Webkul\MCP\DevTools;

class TestGenerator
{
    public function __construct(
        protected FileManager $fileManager
    ) {}

    /**
     * Generate a Pest test file for a given class.
     *
     * @param  string  $packageName  e.g. "MyPlugin" or "Webkul/MyPlugin"
     * @param  string  $className  e.g. "Repositories/CredentialRepository" or "Webkul\MyPlugin\Repositories\CredentialRepository"
     * @return string The generated file path, relative to the application root.
     *
     * @throws \InvalidArgumentException when the package or class name is not a valid PHP name.
     */
    public function generate(string $packageName, string $className): string
    {
        $package = $this->segments($packageName, 'mcp::app.dev.test-generator.invalid-package');

        if (count($package) === 1) {
            array_unshift($package, 'Webkul');
        }

        if (count($package) !== 2) {
            throw new \InvalidArgumentException(trans('mcp::app.dev.test-generator.invalid-package', ['name' => $packageName]));
        }

        $class = $this->segments($className, 'mcp::app.dev.test-generator.invalid-class');

        if (array_slice($class, 0, 2) === $package && count($class) > 2) {
            $class = array_slice($class, 2);
        }

        $baseClassName = array_pop($class);
        $namespace = implode('\\', [...$package, ...$class]);
        $directory = implode('/', ['packages', ...$package, 'tests', 'Unit', ...$class]);
        $testPath = "{$directory}/{$baseClassName}Test.php";

        $content = <<<PHP
<?php

use {$namespace}\\{$baseClassName};

it('loads the {$baseClassName} class', function () {
    expect(class_exists({$baseClassName}::class))->toBeTrue();
});

PHP;

        $this->fileManager->create($testPath, $content);

        return $testPath;
    }

    /**
     * Split a slash or backslash separated name into validated PHP identifier segments.
     *
     * @return list<string>
     */
    protected function segments(string $name, string $errorKey): array
    {
        $segments = array_values(array_filter(
            preg_split('#[\\\\/]+#', trim($name)) ?: [],
            fn (string $segment): bool => $segment !== ''
        ));

        foreach ($segments as $segment) {
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment)) {
                throw new \InvalidArgumentException(trans($errorKey, ['name' => $name]));
            }
        }

        if ($segments === []) {
            throw new \InvalidArgumentException(trans($errorKey, ['name' => $name]));
        }

        return $segments;
    }
}
