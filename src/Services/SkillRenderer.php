<?php

namespace Webkul\MCP\Services;

class SkillRenderer
{
    /**
     * Render a loaded skill as the instructions an AI client follows.
     *
     * @param  array<string, mixed>  $skill
     * @param  array<string, mixed>  $input
     */
    public function render(array $skill, array $input = []): string
    {
        $output = "# Skill: {$skill['name']}\n\n";
        $output .= ($skill['description'] ?? '')."\n\n";
        $output .= "## Instruction Content\n\n";
        $output .= $skill['content'] ?? 'No content available.';

        if ($input !== []) {
            $output .= "\n\n## Input\n\n```json\n".json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n```";
        }

        $additionalDocs = $this->relatedDocuments($skill);

        if ($additionalDocs !== []) {
            $output .= "\n\n## Related Documentation\n";

            foreach ($additionalDocs as $doc) {
                $output .= "- {$doc}\n";
            }

            $output .= "\n*You can request content from these files if needed.*";
        }

        return $output;
    }

    /**
     * @param  array<string, mixed>  $skill
     * @return list<string>
     */
    protected function relatedDocuments(array $skill): array
    {
        if (! empty($skill['files'])) {
            return array_values(array_filter(array_keys($skill['files']), fn (int|string $file): bool => str_ends_with($file, '.md')));
        }

        if (! isset($skill['path']) || ! is_dir($dir = dirname($skill['path']))) {
            return [];
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..', 'SKILL.md']);

        return array_values(array_filter($files, fn (string $file): bool => str_ends_with($file, '.md')));
    }
}
