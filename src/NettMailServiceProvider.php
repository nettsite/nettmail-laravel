<?php

namespace NettSite\NettMail;

use NettSite\NettMail\Commands\NettMailCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NettMailServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('nettmail')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_nettmail_table')
            ->hasCommand(NettMailCommand::class);
    }
}
