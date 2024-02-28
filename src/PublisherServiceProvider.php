<?php

namespace Plank\Publisher;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Plank\Publisher\Commands\PublisherMigrations;
use Plank\Publisher\Middleware\PublisherMiddleware;
use Plank\Publisher\Routing\PublisherUrlGenerator;
use Plank\Publisher\Services\PublisherService;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PublisherServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('publisher')
            ->hasConfigFile()
            ->hasCommand(PublisherMigrations::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command->startWith(function (InstallCommand $command) {
                    $command->line("🖊️  Laravel Publisher Installer... \n");

                    if (! $command->confirm('⚠️ Please ensure you have added the \Plank\Publisher\Contracts\Publishable interface to the models you wish to be publishable. Continue?')) {
                        throw new \Exception('Installation aborted.');
                    }

                    if ($command->confirm('Would you like to publish the config file?')) {
                        $command->publishConfigFile();
                    }

                    if ($command->confirm('Would you like to add the Publisher migrations?')) {
                        $command->call('publisher:migrations');
                        $command->askToRunMigrations();
                    }
                });

                $command->endWith(function (InstallCommand $command) {
                    $command->info('✅ Installation complete.');

                    $command->askToStarRepoOnGitHub('plank/publisher');
                });
            });
    }

    public function bootingPackage()
    {
        $this->bindService()
            ->defineGates()
            ->overrideUrlGenerator()
            ->thisAddBlueprintMacro()
            ->registerMiddleware();
    }

    public function bindService(): self
    {
        if (! $this->app->bound('publisher')) {
            $this->app->scoped('publisher', fn () => new PublisherService);
        }

        return $this;
    }

    protected function defineGates(): self
    {
        if (! Gate::has('publish')) {
            Gate::define('publish', function ($user, $model) {
                return $user !== null;
            });
        }

        if (! Gate::has('unpublish')) {
            Gate::define('unpublish', function ($user, $model) {
                return $user !== null;
            });
        }

        if (! Gate::has('view-draft-content')) {
            Gate::define('view-draft-content', function ($user) {
                return $user !== null;
            });
        }

        return $this;
    }

    protected function overrideUrlGenerator(): self
    {
        if (config()->get('publisher.urls.rewrite') !== true) {
            return $this;
        }

        $this->app->extend('url', function (UrlGenerator $url, $app) {
            $routes = $app['router']->getRoutes();

            $url = new PublisherUrlGenerator(
                $routes,
                $app->rebinding(
                    'request', function ($app, $request) {
                        $app['url']->setRequest($request);
                    }
                ), $app['config']['app.asset_url']
            );

            $url->setSessionResolver(function () {
                return $this->app['session'] ?? null;
            });

            $url->setKeyResolver(function () {
                return $this->app->make('config')->get('app.key');
            });

            return $url;
        });

        return $this;
    }

    protected function thisAddBlueprintMacro(): self
    {
        Blueprint::macro('before', function ($columnName, $newColumnName, $type = 'string', $length = null) {
            /** @var Blueprint $this */
            $columns = $this->columns;
            $targetIndex = null;

            foreach ($columns as $index => $column) {
                if ($column['name'] === $columnName) {
                    $targetIndex = $index;
                    break;
                }
            }

            // Create a new column definition
            $newColumn = $this->addColumn($type, $newColumnName, compact('length'));

            // If target column is found, adjust the order
            if ($targetIndex !== null) {
                array_splice($this->columns, $targetIndex, 0, [$newColumn]);
            } else {
                // If target column not found, just add the column (you might want to handle this differently)
                $this->columns[] = $newColumn;
            }

            return $newColumn;
        });

        return $this;
    }

    protected function registerMiddleware(): self
    {
        if (config('publisher.middleware.enabled') !== true) {
            return $this;
        }

        if (config('publisher.middleware.global') === true) {
            $this->app->make(\Illuminate\Contracts\Http\Kernel::class)
                ->pushMiddleware(PublisherMiddleware::class);
        } else {
            $this->app['router']->aliasMiddleware('publisher', PublisherMiddleware::class);
        }

        return $this;
    }
}
