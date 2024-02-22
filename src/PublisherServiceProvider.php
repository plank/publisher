<?php

namespace Plank\Publisher;

use Illuminate\Support\Facades\Gate;
use Plank\Publisher\Repositories\PublisherRepository;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PublisherServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name('publisher');

        if (! $this->app->bound('publisher')) {
            $this->app->scoped('publisher', fn () => new PublisherRepository);
        }

        if (! Gate::has('publishing')) {
            Gate::define('publishing', function ($user, $model) {
                return $user !== null;
            });
        }

        if (! Gate::has('unpublishing')) {
            Gate::define('unpublishing', function ($user, $model) {
                return $user !== null;
            });
        }
    }
}
