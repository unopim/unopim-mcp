<?php

use Illuminate\Support\Facades\File;
use Webkul\MCP\DevTools\FileManager;
use Webkul\MCP\DevTools\PluginGenerator;

beforeEach(function () {
    $this->fileManager = new FileManager;
    $this->generator = new PluginGenerator($this->fileManager);
});

afterEach(function () {
    $names = ['ConnectorTest', 'CoreExtTest', 'GenericTest'];

    foreach ($names as $name) {
        $base = base_path("packages/Webkul/{$name}");
        if (File::exists($base)) {
            File::deleteDirectory($base);
        }
    }
});

it('generates a connector plugin with all required files', function () {
    $result = $this->generator->generate('ConnectorTest', 'connector');

    expect($result['name'])->toBe('ConnectorTest');
    expect($result['message'])->toContain('generated successfully');
    expect($result['files'])->toBeArray();

    $base = base_path('packages/Webkul/ConnectorTest');

    // Check core files exist
    expect(File::exists($base.'/composer.json'))->toBeTrue();
    expect(File::exists($base.'/src/Config/acl.php'))->toBeTrue();
    expect(File::exists($base.'/src/Config/menu.php'))->toBeTrue();
    expect(File::exists($base.'/src/Config/importers.php'))->toBeTrue();
    expect(File::exists($base.'/src/Config/exporters.php'))->toBeTrue();
    expect(File::exists($base.'/src/Contracts/Credential.php'))->toBeTrue();
    expect(File::exists($base.'/src/Providers/ConnectorTestServiceProvider.php'))->toBeTrue();
    expect(File::exists($base.'/src/Providers/ModuleServiceProvider.php'))->toBeTrue();
    expect(File::exists($base.'/src/Models/Credential.php'))->toBeTrue();
    expect(File::exists($base.'/src/Models/CredentialProxy.php'))->toBeTrue();
    expect(File::exists($base.'/src/Repositories/CredentialRepository.php'))->toBeTrue();
    expect(File::exists($base.'/src/Http/Controllers/CredentialController.php'))->toBeTrue();

    // Verify composer.json content
    $composerJson = json_decode(File::get($base.'/composer.json'), true);
    expect($composerJson['name'])->toBe('webkul/connectortest');
    expect($composerJson['description'])->toContain('Connector Plugin');
    expect($composerJson['autoload']['psr-4'])->toHaveKey('Webkul\\ConnectorTest\\');
});

it('generates a core-extension plugin with minimal files', function () {
    $result = $this->generator->generate('CoreExtTest', 'core-extension');

    expect($result['name'])->toBe('CoreExtTest');
    expect($result['message'])->toContain('generated successfully');

    $base = base_path('packages/Webkul/CoreExtTest');

    // Core-extension has fewer files
    expect(File::exists($base.'/composer.json'))->toBeTrue();
    expect(File::exists($base.'/src/Providers/CoreExtTestServiceProvider.php'))->toBeTrue();
    expect(File::exists($base.'/src/Config/acl.php'))->toBeTrue();
    expect(File::exists($base.'/src/Config/menu.php'))->toBeTrue();

    // Should NOT have connector-specific files
    expect(File::exists($base.'/src/Models/Credential.php'))->toBeFalse();
    expect(File::exists($base.'/src/Repositories/CredentialRepository.php'))->toBeFalse();
});

it('generates a generic plugin with minimal files', function () {
    $result = $this->generator->generate('GenericTest', 'generic');

    expect($result['name'])->toBe('GenericTest');

    $base = base_path('packages/Webkul/GenericTest');

    // Generic has the fewest files
    expect(File::exists($base.'/composer.json'))->toBeTrue();
    expect(File::exists($base.'/src/Providers/GenericTestServiceProvider.php'))->toBeTrue();

    // Should NOT have config files
    expect(File::exists($base.'/src/Config/acl.php'))->toBeFalse();
});

it('throws exception for unsupported plugin type', function () {
    expect(fn () => $this->generator->generate('BadPlugin', 'invalid-type'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported plugin type');
});

it('throws exception when plugin already exists', function () {
    // Create the plugin first
    $this->generator->generate('ConnectorTest', 'connector');

    // Attempt to create it again
    expect(fn () => $this->generator->generate('ConnectorTest', 'connector'))
        ->toThrow(RuntimeException::class, 'already exists');
});

it('converts plugin name to StudlyCase', function () {
    $result = $this->generator->generate('generic_test', 'generic');

    expect($result['name'])->toBe('GenericTest');
    expect($result['path'])->toBe('packages/Webkul/GenericTest');
});

it('generates valid ACL config for connector type', function () {
    $this->generator->generate('ConnectorTest', 'connector');

    $aclContent = File::get(base_path('packages/Webkul/ConnectorTest/src/Config/acl.php'));

    expect($aclContent)->toContain("'key' => 'connector-test'");
    expect($aclContent)->toContain("'name' => 'ConnectorTest'");
});

it('generates migration with correct table name', function () {
    $this->generator->generate('ConnectorTest', 'connector');

    $migrationDir = base_path('packages/Webkul/ConnectorTest/src/Database/Migration');
    $files = File::files($migrationDir);

    expect(count($files))->toBeGreaterThan(0);

    $migrationContent = File::get($files[0]->getPathname());
    expect($migrationContent)->toContain('wk_connector_test_credentials');
    expect($migrationContent)->toContain('Schema::create');
});

it('rejects a plugin name that would leave the packages directory', function (string $name) {
    expect(fn () => $this->generator->generate($name, 'generic'))
        ->toThrow(InvalidArgumentException::class);

    expect(File::exists(base_path('packages/Evil')))->toBeFalse()
        ->and(File::exists(base_path('Evil')))->toBeFalse();
})->with([
    '../../Evil',
    '../Evil',
    'Evil/../../X',
    '1Evil',
    '',
]);
