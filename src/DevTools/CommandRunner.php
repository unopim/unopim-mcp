<?php

namespace Webkul\MCP\DevTools;

use Symfony\Component\Process\Process;

class CommandRunner
{
    /**
     * Allowed base commands.
     */
    protected array $allowedCommands = [
        'php artisan',
        'composer',
    ];

    /**
     * Commands that evaluate arbitrary code, keyed by the tool that runs them.
     *
     * @var array<string, list<string>>
     */
    protected array $blockedCommands = [
        'artisan' => ['tinker'],
        'composer' => ['exec', 'global'],
    ];

    /**
     * Global options that take their value as the next token, keyed by tool.
     *
     * @var array<string, list<string>>
     */
    protected array $optionsWithValues = [
        'artisan' => ['--env'],
        'composer' => ['--working-dir', '-d'],
    ];

    /**
     * Parse and run a command safely without invoking a shell.
     */
    public function run(string $command): string
    {
        $tokens = $this->parseCommand($command);
        $this->ensureCommandIsAllowed($tokens);

        $process = new Process($tokens, base_path());
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
        }

        return $process->getOutput();
    }

    /**
     * Parse the string into an array of arguments to prevent shell interpolation.
     */
    private function parseCommand(string $command): array
    {
        // Simple tokenization by space, respecting quotes.
        $tokens = str_getcsv($command, ' ', '"');
        $tokens = array_values(array_filter($tokens, fn ($t) => trim($t) !== ''));

        if (empty($tokens)) {
            throw new \RuntimeException('Command is empty.');
        }

        return $tokens;
    }

    /**
     * Ensure the command starts with an allowed base command and contains no shell syntax.
     */
    private function ensureCommandIsAllowed(array $tokens): void
    {
        $isAllowed = false;

        if ($tokens[0] === 'php' && isset($tokens[1]) && $tokens[1] === 'artisan') {
            $isAllowed = true;
        } elseif ($tokens[0] === 'composer') {
            $isAllowed = true;
        }

        if (! $isAllowed) {
            throw new \RuntimeException('Command not allowed: '.htmlspecialchars($tokens[0]));
        }

        $tool = $tokens[0] === 'composer' ? 'composer' : 'artisan';

        $this->ensureCommandIsNotBlocked($tool, array_slice($tokens, $tool === 'composer' ? 1 : 2));

        foreach ($tokens as $token) {
            // Even though array Process execution protects against shell operators,
            // we proactively block them as an additional sanitization measure.
            if (preg_match('/[;&|`<>]/', $token) || str_contains($token, '$(')) {
                throw new \RuntimeException('Dangerous characters detected in command arguments.');
            }
        }
    }

    /**
     * Refuse commands that evaluate code, including the abbreviations Symfony Console
     * resolves (`tink` runs `tinker`).
     *
     * @param  list<string>  $arguments
     */
    private function ensureCommandIsNotBlocked(string $tool, array $arguments): void
    {
        $name = $this->commandName($tool, $arguments);

        foreach ($this->blockedCommands[$tool] as $blocked) {
            $isAbbreviation = $name !== null && str_starts_with($blocked, $name);

            if ($isAbbreviation || in_array($blocked, array_map('strtolower', $arguments), true)) {
                throw new \RuntimeException(trans('mcp::app.dev.command-runner.blocked', ['command' => $blocked]));
            }
        }
    }

    /**
     * The lower-cased command name: the first argument that is neither an option nor an option's value.
     *
     * @param  list<string>  $arguments
     */
    private function commandName(string $tool, array $arguments): ?string
    {
        $skipNext = false;

        foreach ($arguments as $argument) {
            if ($skipNext) {
                $skipNext = false;

                continue;
            }

            if (str_starts_with($argument, '-')) {
                $skipNext = in_array($argument, $this->optionsWithValues[$tool], true);

                continue;
            }

            return strtolower($argument);
        }

        return null;
    }
}
