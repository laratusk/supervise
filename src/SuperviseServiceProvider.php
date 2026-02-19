<?php

declare(strict_types=1);

namespace Laratusk\Supervise;

use Laratusk\Supervise\Commands\CompileCommand;
use Laratusk\Supervise\Commands\InstallCommand;
use Laratusk\Supervise\Commands\LinkCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SuperviseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('supervise')
            ->hasConfigFile()
            ->hasCommands([
                CompileCommand::class,
                LinkCommand::class,
                InstallCommand::class,
            ]);
    }
}
