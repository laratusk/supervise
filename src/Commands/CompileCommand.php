<?php

declare(strict_types=1);

namespace Laratusk\Supervise\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Laratusk\Supervise\Exceptions\ValidationException;
use Laratusk\Supervise\Services\ConfigValidator;
use Laratusk\Supervise\Services\SupervisorCompiler;

class CompileCommand extends Command
{
    protected $signature = 'supervise:compile {--reload : Run supervisorctl reread && update after compile}';

    protected $description = 'Compile Supervisor configuration files from Laravel config';

    public function handle(): int
    {
        /** @var array<string, mixed>|null $config */
        $config = config('supervise');

        if (! is_array($config)) {
            $this->error('Could not load supervise config.');

            return self::FAILURE;
        }

        $validator = new ConfigValidator;

        try {
            $validator->validate($config);
        } catch (ValidationException $e) {
            foreach ($e->getErrors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $outputRelPath = (string) ($config['output_path'] ?? '.supervisor/conf.d');
        $outputPath = str_starts_with($outputRelPath, DIRECTORY_SEPARATOR)
            ? $outputRelPath
            : base_path($outputRelPath);

        $compiler = new SupervisorCompiler($config, base_path());
        $files = $compiler->compile($outputPath);

        foreach ($files as $file) {
            $this->line('✓ Compiled: '.basename($file));
        }

        /** @var array<string, mixed> $workers */
        $workers = $config['workers'] ?? [];

        /** @var array<string, mixed> $groups */
        $groups = $config['groups'] ?? [];

        $workerCount = count($workers);
        $groupCount = count($groups);

        $this->info("Compiled {$workerCount} worker(s) and {$groupCount} group(s)");

        if ($this->option('reload')) {
            $this->line('Reloading Supervisor...');
            $result = Process::run('supervisorctl reread && supervisorctl update');
            $this->line($result->output());

            if ($result->failed()) {
                $this->error($result->errorOutput());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
