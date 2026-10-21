<?php

namespace Webkul\MCP\Services;

use Symfony\Component\Yaml\Yaml;

class SkillParser
{
    /**
     * Parse a SKILL.md file and extract its YAML frontmatter and content.
     *
     * @return array{name: string, description: string, license: string, parameters: array<string,mixed>, metadata: array<string,mixed>, content: string, path: string}
     *
     * @throws \InvalidArgumentException When the file does not exist.
     */
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("SKILL.md not found at path: {$filePath}");
        }

        $raw = file_get_contents($filePath);

        return array_merge(
            $this->extractFrontmatter($raw),
            [
                'content' => $this->extractBody($raw),
                'path' => $filePath,
            ]
        );
    }

    /**
     * Extract and parse the YAML frontmatter from the given markdown content.
     *
     * @return array{name: string, description: string, license: string, metadata: array<string,mixed>}
     */
    private function extractFrontmatter(string $content): array
    {
        $defaults = [
            'name' => '',
            'description' => '',
            'license' => '',
            'metadata' => [],
        ];

        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n?/s', $content, $matches)) {
            return $defaults;
        }

        $parsed = Yaml::parse($matches[1]);

        if (! is_array($parsed)) {
            return $defaults;
        }

        return [
            'name' => (string) ($parsed['name'] ?? ''),
            'description' => (string) ($parsed['description'] ?? ''),
            'license' => (string) ($parsed['license'] ?? ''),
            'parameters' => (array) ($parsed['parameters'] ?? []),
            'metadata' => (array) ($parsed['metadata'] ?? []),
        ];
    }

    /**
     * Extract the markdown body (everything after the closing frontmatter delimiter).
     */
    private function extractBody(string $content): string
    {
        if (preg_match('/^---\s*\n.*?\n---\s*\n?(.*)/s', $content, $matches)) {
            return trim($matches[1]);
        }

        return trim($content);
    }
}
