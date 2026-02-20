<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;

beforeEach(function (): void {
    $this->tmpDir = $this->makeTempDir();
});

afterEach(function (): void {
    $this->removeDir($this->tmpDir);
});

it('compiles worker and creates conf file', function (): void {
    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $this->tmpDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [
            'horizon' => ['command' => 'php artisan horizon'],
        ],
        'groups' => [],
    ]);

    $this->artisan('supervise:compile')
        ->expectsOutputToContain('✓ Compiled: horizon.conf')
        ->expectsOutputToContain('Compiled 1 worker(s) and 0 group(s)')
        ->assertExitCode(0);

    expect(file_exists($this->tmpDir.'/horizon.conf'))->toBeTrue();
});

it('compiles worker with custom command and creates conf file', function (): void {
    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $this->tmpDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [
            'emails' => [
                'command' => 'php artisan queue:work --queue=emails',
            ],
        ],
        'groups' => [],
    ]);

    $this->artisan('supervise:compile')
        ->expectsOutputToContain('✓ Compiled: emails.conf')
        ->assertExitCode(0);

    expect(file_exists($this->tmpDir.'/emails.conf'))->toBeTrue();

    $content = file_get_contents($this->tmpDir.'/emails.conf');
    expect($content)->toContain('[program:emails]');
});

it('compiles reverb worker and creates conf file', function (): void {
    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $this->tmpDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [
            'reverb' => ['command' => 'php artisan reverb:start'],
        ],
        'groups' => [],
    ]);

    $this->artisan('supervise:compile')
        ->expectsOutputToContain('✓ Compiled: reverb.conf')
        ->assertExitCode(0);
});

it('compiles group config files', function (): void {
    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $this->tmpDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [
            'horizon' => ['command' => 'php artisan horizon'],
        ],
        'groups' => [
            'laravel' => ['horizon'],
        ],
    ]);

    $this->artisan('supervise:compile')
        ->expectsOutputToContain('✓ Compiled: horizon.conf')
        ->expectsOutputToContain('✓ Compiled: laravel.conf')
        ->expectsOutputToContain('Compiled 1 worker(s) and 1 group(s)')
        ->assertExitCode(0);

    expect(file_exists($this->tmpDir.'/laravel.conf'))->toBeTrue();
});

it('automatically creates output directory', function (): void {
    $newDir = $this->tmpDir.'/auto-created/conf.d';

    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $newDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [
            'horizon' => ['command' => 'php artisan horizon'],
        ],
        'groups' => [],
    ]);

    $this->artisan('supervise:compile')->assertExitCode(0);

    expect(is_dir($newDir))->toBeTrue();
    expect(file_exists($newDir.'/horizon.conf'))->toBeTrue();
});

it('is idempotent and overwrites existing files', function (): void {
    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $this->tmpDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [
            'horizon' => ['command' => 'php artisan horizon'],
        ],
        'groups' => [],
    ]);

    $this->artisan('supervise:compile')->assertExitCode(0);
    file_put_contents($this->tmpDir.'/horizon.conf', 'MODIFIED');

    $this->artisan('supervise:compile')->assertExitCode(0);

    $content = file_get_contents($this->tmpDir.'/horizon.conf');
    expect($content)->not->toBe('MODIFIED');
    expect($content)->toContain('[program:horizon]');
});

it('shows validation errors when config is invalid', function (): void {
    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $this->tmpDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [],
        'groups' => [],
    ]);

    $this->artisan('supervise:compile')->assertExitCode(1);
});

it('shows validation error for missing command', function (): void {
    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $this->tmpDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [
            'emails' => [],
        ],
        'groups' => [],
    ]);

    $this->artisan('supervise:compile')->assertExitCode(1);
});

it('runs supervisorctl when --reload flag is set', function (): void {
    Process::fake([
        'supervisorctl reread && supervisorctl update' => Process::result(
            output: "No config updates to processes\nUpdated Supervisor\n",
        ),
    ]);

    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $this->tmpDir,
        'defaults' => $this->defaultDirectives(),
        'workers' => [
            'horizon' => ['command' => 'php artisan horizon'],
        ],
        'groups' => [],
    ]);

    $this->artisan('supervise:compile', ['--reload' => true])
        ->expectsOutputToContain('Reloading Supervisor...')
        ->assertExitCode(0);

    Process::assertRan('supervisorctl reread && supervisorctl update');
});

it('merges worker config with defaults', function (): void {
    $defaults = $this->defaultDirectives();
    $defaults['numprocs'] = 1;
    $defaults['user'] = 'www-data';

    Config::set('supervise', [
        'conf_path' => '/etc/supervisor/conf.d',
        'output_path' => $this->tmpDir,
        'defaults' => $defaults,
        'workers' => [
            'horizon' => [
                'command' => 'php artisan horizon',
                'numprocs' => 2,
            ],
        ],
        'groups' => [],
    ]);

    $this->artisan('supervise:compile')->assertExitCode(0);

    $content = file_get_contents($this->tmpDir.'/horizon.conf');
    expect($content)
        ->toContain('numprocs=2')   // worker override
        ->toContain('user=www-data'); // default
});
