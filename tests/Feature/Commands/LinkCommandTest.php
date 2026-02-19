<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->sourceDir = $this->makeTempDir();
    $this->targetDir = $this->makeTempDir();
});

afterEach(function (): void {
    $this->removeDir($this->sourceDir);
    $this->removeDir($this->targetDir);
});

it('creates symlinks for compiled conf files', function (): void {
    // Create a fake compiled file
    file_put_contents($this->sourceDir.'/horizon.conf', '[program:horizon]');

    Config::set('supervise', [
        'conf_path' => $this->targetDir,
        'output_path' => $this->sourceDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [],
        'groups' => [],
    ]);

    $this->artisan('supervise:link')
        ->expectsOutputToContain('✓ Linked: horizon.conf')
        ->expectsOutputToContain("Linked 1 file(s) to {$this->targetDir}")
        ->assertExitCode(0);

    expect(is_link($this->targetDir.'/horizon.conf'))->toBeTrue();
    expect(readlink($this->targetDir.'/horizon.conf'))->toBe($this->sourceDir.'/horizon.conf');
});

it('creates symlinks for multiple conf files', function (): void {
    file_put_contents($this->sourceDir.'/horizon.conf', '[program:horizon]');
    file_put_contents($this->sourceDir.'/emails.conf', '[program:emails]');

    Config::set('supervise', [
        'conf_path' => $this->targetDir,
        'output_path' => $this->sourceDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [],
        'groups' => [],
    ]);

    $this->artisan('supervise:link')
        ->expectsOutputToContain("Linked 2 file(s) to {$this->targetDir}")
        ->assertExitCode(0);

    expect(is_link($this->targetDir.'/horizon.conf'))->toBeTrue();
    expect(is_link($this->targetDir.'/emails.conf'))->toBeTrue();
});

it('errors when output directory does not exist', function (): void {
    Config::set('supervise', [
        'conf_path' => $this->targetDir,
        'output_path' => '/nonexistent/path',
        'defaults' => $this->defaultDirectives(),
        'workers' => [],
        'groups' => [],
    ]);

    $this->artisan('supervise:link')
        ->expectsOutputToContain('No compiled files found. Run supervise:compile first.')
        ->assertExitCode(1);
});

it('errors when no conf files exist in output directory', function (): void {
    Config::set('supervise', [
        'conf_path' => $this->targetDir,
        'output_path' => $this->sourceDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [],
        'groups' => [],
    ]);

    $this->artisan('supervise:link')
        ->expectsOutputToContain('No compiled files found. Run supervise:compile first.')
        ->assertExitCode(1);
});

it('recreates symlinks on repeated runs', function (): void {
    file_put_contents($this->sourceDir.'/horizon.conf', '[program:horizon]');

    Config::set('supervise', [
        'conf_path' => $this->targetDir,
        'output_path' => $this->sourceDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [],
        'groups' => [],
    ]);

    $this->artisan('supervise:link')->assertExitCode(0);
    $this->artisan('supervise:link')->assertExitCode(0);

    expect(is_link($this->targetDir.'/horizon.conf'))->toBeTrue();
    expect(readlink($this->targetDir.'/horizon.conf'))->toBe($this->sourceDir.'/horizon.conf');
});

it('uses correct absolute paths for symlinks', function (): void {
    file_put_contents($this->sourceDir.'/horizon.conf', '[program:horizon]');

    Config::set('supervise', [
        'conf_path' => $this->targetDir,
        'output_path' => $this->sourceDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [],
        'groups' => [],
    ]);

    $this->artisan('supervise:link')->assertExitCode(0);

    $linkPath = $this->targetDir.'/horizon.conf';
    expect(str_starts_with((string) readlink($linkPath), '/'))->toBeTrue();
});
