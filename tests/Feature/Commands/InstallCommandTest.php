<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->tmpBase = $this->makeTempDir();
    $this->app->setBasePath($this->tmpBase);

    // Create a basic .gitignore to test against
    file_put_contents($this->tmpBase.'/.gitignore', "/vendor\n");
});

afterEach(function (): void {
    $this->removeDir($this->tmpBase);
});

it('creates .supervisor/conf.d directory', function (): void {
    $this->artisan('supervise:install')
        ->expectsOutputToContain('✓ Created directory: .supervisor/conf.d/')
        ->assertExitCode(0);

    expect(is_dir($this->tmpBase.'/.supervisor/conf.d'))->toBeTrue();
});

it('creates storage/logs/supervisor directory', function (): void {
    $this->artisan('supervise:install')
        ->expectsOutputToContain('✓ Created directory: storage/logs/supervisor/')
        ->assertExitCode(0);

    expect(is_dir($this->tmpBase.'/storage/logs/supervisor'))->toBeTrue();
});

it('adds .supervisor/ to .gitignore', function (): void {
    $this->artisan('supervise:install')
        ->expectsOutputToContain('✓ Added .supervisor/ to .gitignore')
        ->assertExitCode(0);

    $gitignore = file_get_contents($this->tmpBase.'/.gitignore');
    expect($gitignore)->toContain('.supervisor/');
});

it('does not duplicate .supervisor/ in .gitignore if already present', function (): void {
    file_put_contents($this->tmpBase.'/.gitignore', "/vendor\n.supervisor/\n");

    $this->artisan('supervise:install')
        ->expectsOutputToContain('.supervisor/ already in .gitignore')
        ->assertExitCode(0);

    $gitignore = (string) file_get_contents($this->tmpBase.'/.gitignore');
    expect(substr_count($gitignore, '.supervisor/'))->toBe(1);
});

it('shows already exists message when directories exist', function (): void {
    mkdir($this->tmpBase.'/.supervisor/conf.d', 0755, true);
    mkdir($this->tmpBase.'/storage/logs/supervisor', 0755, true);

    $this->artisan('supervise:install')
        ->expectsOutputToContain('Directory already exists: .supervisor/conf.d/')
        ->expectsOutputToContain('Directory already exists: storage/logs/supervisor/')
        ->assertExitCode(0);
});

it('publishes config file', function (): void {
    $this->artisan('supervise:install')
        ->expectsOutputToContain('✓ Published config file: config/supervise.php')
        ->assertExitCode(0);
});

it('shows success message', function (): void {
    $this->artisan('supervise:install')
        ->expectsOutputToContain('Supervise installed successfully!')
        ->assertExitCode(0);
});
