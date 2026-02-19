<?php

declare(strict_types=1);

namespace Laratusk\Supervise\Tests;

use Laratusk\Supervise\SuperviseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SuperviseServiceProvider::class,
        ];
    }

    /**
     * Create a temporary directory and return its path.
     */
    protected function makeTempDir(): string
    {
        $dir = sys_get_temp_dir().'/supervise-test-'.uniqid();
        mkdir($dir, 0755, true);

        return $dir;
    }

    /**
     * Recursively remove a directory and its contents.
     */
    protected function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->removeDir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSuperviseConfig(): array
    {
        return [
            'conf_path' => '/etc/supervisor/conf.d',
            'output_path' => '.supervisor/conf.d',
            'defaults' => $this->defaultDirectives(),
            'workers' => [],
            'groups' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultDirectives(): array
    {
        return [
            'process_name' => '%(program_name)s_%(process_num)02d',
            'numprocs' => 1,
            'numprocs_start' => 0,
            'priority' => 999,
            'autostart' => true,
            'startsecs' => 1,
            'startretries' => 3,
            'autorestart' => 'unexpected',
            'exitcodes' => '0',
            'stopsignal' => 'TERM',
            'stopwaitsecs' => 3600,
            'stopasgroup' => true,
            'killasgroup' => true,
            'user' => 'root',
            'directory' => null,
            'umask' => null,
            'environment' => null,
            'redirect_stderr' => true,
            'stdout_logfile' => 'AUTO',
            'stdout_logfile_maxbytes' => '50MB',
            'stdout_logfile_backups' => 10,
            'stdout_capture_maxbytes' => 0,
            'stdout_events_enabled' => false,
            'stdout_syslog' => false,
            'stderr_logfile' => 'AUTO',
            'stderr_logfile_maxbytes' => '50MB',
            'stderr_logfile_backups' => 10,
            'stderr_capture_maxbytes' => 0,
            'stderr_events_enabled' => false,
            'stderr_syslog' => false,
            'serverurl' => null,
        ];
    }
}
