<?php

namespace Webkul\MCP\DevTools;

use Illuminate\Support\Facades\File;
use Webkul\MCP\Contracts\FileManagerInterface;

class FileManager implements FileManagerInterface
{
    /**
     * Create a new file with the given content.
     */
    public function create(string $path, string $content): bool
    {
        $realPath = $this->validateAndResolvePath($path);

        if (File::exists($realPath)) {
            throw new \RuntimeException("File already exists at: {$path}");
        }

        File::ensureDirectoryExists(dirname($realPath));

        return File::put($realPath, $content) !== false;
    }

    /**
     * Read the content of a file.
     */
    public function read(string $path): string
    {
        $realPath = $this->validateAndResolvePath($path);

        if (! File::exists($realPath)) {
            throw new \RuntimeException("File not found at: {$path}");
        }

        return File::get($realPath);
    }

    /**
     * Update an existing file with the given content.
     */
    public function update(string $path, string $content): bool
    {
        $realPath = $this->validateAndResolvePath($path);

        if (! File::exists($realPath)) {
            throw new \RuntimeException("File does not exist at: {$path}");
        }

        return File::put($realPath, $content) !== false;
    }

    /**
     * Validate the path to ensure it is within allowed project boundaries.
     *
     * Symlinks are resolved before comparing, and an allowed path only matches a whole
     * directory, so `/var/www/app-evil` is not inside `/var/www/app`.
     */
    private function validateAndResolvePath(string $path): string
    {
        $allowedPaths = config('mcp.allowed_paths', [base_path(), sys_get_temp_dir()]);

        $absolutePath = $this->isAbsolutePath($path)
            ? $path
            : base_path().DIRECTORY_SEPARATOR.$path;

        $resolved = $this->resolveRealPath($this->normalisePath($absolutePath));

        foreach ($allowedPaths as $allowed) {
            $realAllowed = realpath($allowed);

            if ($resolved !== null && $realAllowed !== false && $this->isWithin($resolved, $realAllowed)) {
                return $resolved;
            }
        }

        throw new \RuntimeException("Security: Access to path [{$path}] is restricted. It must be within allowed project boundaries.");
    }

    /**
     * Resolve symlinks through the nearest existing ancestor, keeping the segments that
     * do not exist yet so new files can still be created. A dangling symlink resolves to
     * null, since writing through it would land wherever it points.
     */
    private function resolveRealPath(string $path): ?string
    {
        $missing = [];
        $current = $path;

        while (($real = realpath($current)) === false) {
            if (is_link($current)) {
                return null;
            }

            $parent = dirname($current);

            if ($parent === $current) {
                return $path;
            }

            array_unshift($missing, basename($current));
            $current = $parent;
        }

        return $missing === []
            ? $real
            : rtrim($real, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $missing);
    }

    /**
     * Check whether a resolved path is the allowed directory itself or inside it.
     */
    private function isWithin(string $path, string $directory): bool
    {
        return $path === $directory
            || str_starts_with($path, rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
    }

    /**
     * Normalise a path (resolve .. / .) without requiring the path to exist.
     */
    private function normalisePath(string $path): string
    {
        // Replace all slashes with the OS separator
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $resolved = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                array_pop($resolved);
            } else {
                $resolved[] = $part;
            }
        }

        $result = implode(DIRECTORY_SEPARATOR, $resolved);

        // Re-add leading separator for Linux/Unix paths if the original path was absolute
        if (! $this->isWindowsPath($path) && str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $result = DIRECTORY_SEPARATOR.$result;
        }

        return $result;
    }

    /**
     * Check if a path is on Windows and absolute (e.g., C:\).
     */
    private function isWindowsPath(string $path): bool
    {
        return strlen($path) > 1 && $path[1] === ':';
    }

    /**
     * Check if a path is absolute.
     */
    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || (strlen($path) > 2 && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/'));
    }
}
