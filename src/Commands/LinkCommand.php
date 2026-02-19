<?php

declare(strict_types=1);

namespace Laratusk\Supervise\Commands;

use Illuminate\Console\Command;
use Laratusk\Supervise\Exceptions\CompileException;
use Laratusk\Supervise\Services\SymlinkManager;

class LinkCommand extends Command
{
    protected $signature = 'supervise:link';

    protected $description = 'Symlink compiled Supervisor configuration files to the system conf directory';

    public function handle(): int
    {
        /** @var array<string, mixed>|null $config */
        $config = config('supervise');

        if (! is_array($config)) {
            $this->error('Could not load supervise config.');

            return self::FAILURE;
        }

        $outputRelPath = (string) ($config['output_path'] ?? '.supervisor/conf.d');
        $outputPath = str_starts_with($outputRelPath, DIRECTORY_SEPARATOR)
            ? $outputRelPath
            : base_path($outputRelPath);

        $confPath = (string) ($config['conf_path'] ?? '/etc/supervisor/conf.d');

        if (! is_dir($outputPath)) {
            $this->error('No compiled files found. Run supervise:compile first.');

            return self::FAILURE;
        }

        /** @var list<string>|false $files */
        $files = glob($outputPath.DIRECTORY_SEPARATOR.'*.conf');

        if ($files === false || $files === []) {
            $this->error('No compiled files found. Run supervise:compile first.');

            return self::FAILURE;
        }

        $manager = new SymlinkManager;

        try {
            $linked = $manager->link($files, $confPath);
        } catch (CompileException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($files as $index => $file) {
            $filename = basename($file);
            $target = $linked[$index];
            $this->line("✓ Linked: {$filename} → {$target}");
        }

        $count = count($linked);
        $this->info("Linked {$count} file(s) to {$confPath}");

        return self::SUCCESS;
    }
}
