<?php

use Illuminate\Support\Facades\File;
use Webkul\MCP\DevTools\FileManager;

beforeEach(function () {
    $this->manager = new FileManager;
});

afterEach(function () {
    $cleanupPaths = [
        base_path('storage/mcp_fm_test.txt'),
        base_path('storage/mcp_fm_test_nested/deep/file.txt'),
        base_path('storage/mcp_fm_test_nested/deep'),
        base_path('storage/mcp_fm_test_nested'),
    ];

    foreach ($cleanupPaths as $path) {
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    if (is_dir(base_path('storage/mcp_fm_test_nested'))) {
        File::deleteDirectory(base_path('storage/mcp_fm_test_nested'));
    }
});

it('creates a file with directory auto-creation', function () {
    $path = 'storage/mcp_fm_test_nested/deep/file.txt';

    $result = $this->manager->create($path, 'nested content');

    expect($result)->toBeTrue();
    expect(File::exists(base_path($path)))->toBeTrue();
    expect(File::get(base_path($path)))->toBe('nested content');
});

it('throws exception when creating a file that already exists', function () {
    $path = 'storage/mcp_fm_test.txt';
    File::put(base_path($path), 'existing');

    expect(fn () => $this->manager->create($path, 'new content'))
        ->toThrow(RuntimeException::class, 'File already exists');

    File::delete(base_path($path));
});

it('throws exception when reading a nonexistent file', function () {
    expect(fn () => $this->manager->read('storage/does_not_exist.txt'))
        ->toThrow(RuntimeException::class, 'File not found');
});

it('throws exception when updating a nonexistent file', function () {
    expect(fn () => $this->manager->update('storage/does_not_exist.txt', 'content'))
        ->toThrow(RuntimeException::class, 'File does not exist');
});

it('blocks path traversal with double dots', function () {
    expect(fn () => $this->manager->read('../../etc/passwd'))
        ->toThrow(RuntimeException::class, 'Security');
});

it('blocks path traversal with absolute path outside project', function () {
    expect(fn () => $this->manager->read('/etc/passwd'))
        ->toThrow(RuntimeException::class, 'Security');
});

it('blocks path traversal with encoded dots in relative path', function () {
    expect(fn () => $this->manager->read('storage/../../../etc/shadow'))
        ->toThrow(RuntimeException::class, 'Security');
});

it('allows reading files within the project boundary', function () {
    $path = 'storage/mcp_fm_test.txt';
    File::put(base_path($path), 'safe content');

    $content = $this->manager->read($path);
    expect($content)->toBe('safe content');

    File::delete(base_path($path));
});

it('allows creating and updating within temp directory', function () {
    $tempFile = sys_get_temp_dir().'/mcp_fm_test_'.uniqid().'.txt';

    $result = $this->manager->create($tempFile, 'temp content');
    expect($result)->toBeTrue();

    $content = $this->manager->read($tempFile);
    expect($content)->toBe('temp content');

    $this->manager->update($tempFile, 'updated temp');
    expect($this->manager->read($tempFile))->toBe('updated temp');

    unlink($tempFile);
});

it('handles relative paths correctly by prepending base_path', function () {
    $path = 'storage/mcp_fm_test.txt';

    $this->manager->create($path, 'relative path test');
    expect(File::exists(base_path($path)))->toBeTrue();

    File::delete(base_path($path));
});

it('blocks a sibling directory whose name starts with the project directory name', function () {
    $sibling = base_path().'-mcp-evil';

    expect(fn () => $this->manager->create('../'.basename($sibling).'/x.txt', 'escaped'))
        ->toThrow(RuntimeException::class, 'Security');

    expect(File::exists($sibling.'/x.txt'))->toBeFalse();

    if (is_dir($sibling)) {
        File::deleteDirectory($sibling);
    }
});

it('blocks a symlink inside the project that points outside it', function () {
    $outside = sys_get_temp_dir().'/mcp_fm_outside_'.uniqid();
    $link = base_path('storage/mcp_fm_test_link');

    config(['mcp.allowed_paths' => [base_path()]]);
    File::ensureDirectoryExists($outside);
    @unlink($link);
    symlink($outside, $link);

    try {
        expect(fn () => $this->manager->create('storage/mcp_fm_test_link/x.txt', 'escaped'))
            ->toThrow(RuntimeException::class, 'Security');

        expect(File::exists($outside.'/x.txt'))->toBeFalse();
    } finally {
        @unlink($link);
        File::deleteDirectory($outside);
    }
});

it('blocks writing through a dangling symlink', function () {
    $target = sys_get_temp_dir().'/mcp_fm_dangling_'.uniqid().'.txt';
    $link = base_path('storage/mcp_fm_test_dangling');

    config(['mcp.allowed_paths' => [base_path()]]);
    @unlink($link);
    symlink($target, $link);

    try {
        expect(fn () => $this->manager->create('storage/mcp_fm_test_dangling', 'escaped'))
            ->toThrow(RuntimeException::class, 'Security');

        expect(file_exists($target))->toBeFalse();
    } finally {
        @unlink($link);
        @unlink($target);
    }
});
