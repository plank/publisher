<?php

namespace Plank\Publisher\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;
use Orchestra\Testbench\TestCase as Orchestra;
use Plank\Publisher\PublisherServiceProvider;
use Plank\Publisher\Tests\Helpers\Models\User;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Plank\\Publisher\\Tests\\Helper\\Database\\Factories\\'.class_basename($modelName).'Factory'
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
            PublisherServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('publisher', include_once __DIR__.'/../config/publisher.php');
    }
}
