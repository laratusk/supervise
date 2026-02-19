<?php

declare(strict_types=1);

namespace Laratusk\Supervise\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'supervise:install';

    protected $description = 'Install the Supervise package (create directories, publish config)';

    public function handle(): int
    {
        // Create .supervisor/conf.d output directory
        $outputDir = base_path('.supervisor/conf.d');

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
            $this->line('✓ Created directory: .supervisor/conf.d/');
        } else {
            $this->line('Directory already exists: .supervisor/conf.d/');
        }

        // Create storage/logs/supervisor directory for worker logs
        $logsDir = base_path('storage/logs/supervisor');

        if (! is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
            $this->line('✓ Created directory: storage/logs/supervisor/');
        } else {
            $this->line('Directory already exists: storage/logs/supervisor/');
        }

        // Add .supervisor/ to .gitignore if not already present
        $gitignorePath = base_path('.gitignore');

        if (file_exists($gitignorePath)) {
            $gitignoreContent = (string) file_get_contents($gitignorePath);

            if (! str_contains($gitignoreContent, '.supervisor/')) {
                file_put_contents($gitignorePath, $gitignoreContent."\n.supervisor/\n");
                $this->line('✓ Added .supervisor/ to .gitignore');
            } else {
                $this->line('.supervisor/ already in .gitignore');
            }
        }

        // Publish config file
        $this->callSilent('vendor:publish', ['--tag' => 'supervise-config']);
        $this->line('✓ Published config file: config/supervise.php');

        $this->info('Supervise installed successfully!');
        $this->info('Edit config/supervise.php, then run: php artisan supervise:compile');

        return self::SUCCESS;
    }
}
