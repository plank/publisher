<?php

namespace Plank\Publisher\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;
use Orchestra\Testbench\TestCase as Orchestra;
use Plank\LaravelModelResolver\LaravelModelResolverServiceProvider;
use Plank\LaravelSchemaEvents\LaravelSchemaEventsServiceProvider;
use Plank\Publisher\PublisherServiceProvider;
use Plank\Publisher\Tests\Helpers\Models\User;
use Tests\Publisher\Tests\Helpers\Controllers\PostController;
use Tests\Publisher\Tests\Helpers\Controllers\TestController;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Plank\\Publisher\\Tests\\Helpers\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->artisan('migrate', [
            '--path' => realpath(__DIR__.'/Helpers/Database/Migrations'),
            '--realpath' => true,
        ])->run();

        Auth::setUser(User::create([
            'name' => 'Admin',
        ]));
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelModelResolverServiceProvider::class,
            LaravelSchemaEventsServiceProvider::class,
            PublisherServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');

        $app['router']
            ->middleware('publisher')
            ->get('posts/{id}', [PostController::class, 'show'])
            ->name('posts.show');

        $app['router']
            ->middleware('publisher')
            ->get('pages/{id}', [TestController::class, 'test'])
            ->name('pages.show');

        $app['router']
            ->middleware('publisher')
            ->get('admin', [TestController::class, 'test'])
            ->name('admin');

        $app['router']
            ->middleware('publisher')
            ->get('admin/dashboard', [TestController::class, 'test'])
            ->name('admin.dashboard');

        $app['router']
            ->middleware('publisher')
            ->get('admin/resources/{resource}/{resourceId}/details', [TestController::class, 'test'])
            ->name('admin.show');
    }
}
