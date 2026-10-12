<?php

use Webkul\MCP\DevTools\CommandRunner;

beforeEach(function () {
    $this->runner = new CommandRunner;
});

it('allows php artisan commands', function () {
    // php artisan --version should work in any Laravel environment
    $output = $this->runner->run('php artisan --version');

    expect($output)->toContain('Laravel');
});

it('allows composer commands', function () {
    // composer --version should work
    try {
        $output = $this->runner->run('composer --version');
        expect($output)->toContain('Composer');
    } catch (RuntimeException $e) {
        // If composer is not installed, the error should NOT be about command not allowed
        expect($e->getMessage())->not->toContain('Command not allowed');
    }
});

it('blocks arbitrary system commands', function () {
    expect(fn () => $this->runner->run('ls -la'))
        ->toThrow(RuntimeException::class, 'Command not allowed');
});

it('blocks rm commands', function () {
    expect(fn () => $this->runner->run('rm -rf /'))
        ->toThrow(RuntimeException::class, 'Command not allowed');
});

it('blocks curl commands', function () {
    expect(fn () => $this->runner->run('curl https://example.com'))
        ->toThrow(RuntimeException::class, 'Command not allowed');
});

it('blocks shell operators in artisan commands - semicolon', function () {
    expect(fn () => $this->runner->run('php artisan list; rm -rf /'))
        ->toThrow(RuntimeException::class, 'Dangerous characters');
});

it('blocks shell operators in artisan commands - pipe', function () {
    expect(fn () => $this->runner->run('php artisan list | cat /etc/passwd'))
        ->toThrow(RuntimeException::class, 'Dangerous characters');
});

it('blocks shell operators in artisan commands - ampersand', function () {
    expect(fn () => $this->runner->run('php artisan list & rm -rf /'))
        ->toThrow(RuntimeException::class, 'Dangerous characters');
});

it('blocks shell operators in artisan commands - backtick', function () {
    expect(fn () => $this->runner->run('php artisan list `whoami`'))
        ->toThrow(RuntimeException::class, 'Dangerous characters');
});

it('blocks shell operators in artisan commands - dollar paren', function () {
    expect(fn () => $this->runner->run('php artisan list $(whoami)'))
        ->toThrow(RuntimeException::class, 'Dangerous characters');
});

it('blocks shell operators in artisan commands - redirect', function () {
    expect(fn () => $this->runner->run('php artisan list > /tmp/out'))
        ->toThrow(RuntimeException::class, 'Dangerous characters');
});

it('throws exception for empty command', function () {
    expect(fn () => $this->runner->run(''))
        ->toThrow(RuntimeException::class, 'Command is empty');
});

it('handles quoted arguments correctly', function () {
    expect($this->runner->run('php artisan help "list"'))->toContain('Usage:');
});

it('blocks node commands', function () {
    expect(fn () => $this->runner->run('node -e "process.exit(1)"'))
        ->toThrow(RuntimeException::class, 'Command not allowed');
});

it('blocks python commands', function () {
    expect(fn () => $this->runner->run('python -c "import os"'))
        ->toThrow(RuntimeException::class, 'Command not allowed');
});
