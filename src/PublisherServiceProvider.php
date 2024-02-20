<?php

namespace Plank\Publisher;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Plank\Publisher\Commands\PublisherCommand;

class PublisherServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('publisher')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_publisher_table')
            ->hasCommand(PublisherCommand::class);
    }
}
