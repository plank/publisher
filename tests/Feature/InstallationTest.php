<?php

use Illuminate\Support\Facades\File;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;

use function Pest\Laravel\artisan;

afterEach(function () {
    if (File::exists(config_path('publisher.php'))) {
        File::delete(config_path('publisher.php'));
    }

    foreach (File::allFiles(database_path('migrations')) as $file) {
        File::delete($file->getPathname());
    }
});

it('aborts the install when you dont confirm', function () {
    artisan('publisher:install')
        ->expectsConfirmation('⚠️ Please ensure you have added the \Plank\Publisher\Contracts\Publishable interface to the models you wish to be publishable. Continue?', 'no');

    expect(file_exists(config_path('publisher.php')))->toBeFalse();
})->throws(\Exception::class, 'Installation aborted.');

it('publishes the config file when confirmed', function () {
    File::delete(config_path('publisher.php'));
    expect(file_exists(config_path('publisher.php')))->toBeFalse();

    artisan('publisher:install')
        ->expectsConfirmation('⚠️ Please ensure you have added the \Plank\Publisher\Contracts\Publishable interface to the models you wish to be publishable. Continue?', 'yes')
        ->expectsConfirmation('Would you like to publish the config file?', 'yes')
        ->expectsConfirmation('Would you like to add the Publisher migrations?', 'no')
        ->assertExitCode(0);

    expect(file_exists(config_path('publisher.php')))->toBeTrue();
});

it('does not publish the config file when not confirmed', function () {
    File::delete(config_path('publisher.php'));
    expect(file_exists(config_path('publisher.php')))->toBeFalse();

    artisan('publisher:install')
        ->expectsConfirmation('⚠️ Please ensure you have added the \Plank\Publisher\Contracts\Publishable interface to the models you wish to be publishable. Continue?', 'yes')
        ->expectsConfirmation('Would you like to publish the config file?', 'no')
        ->expectsConfirmation('Would you like to add the Publisher migrations?', 'no')
        ->assertExitCode(0);

    expect(file_exists(config_path('publisher.php')))->toBeFalse();
});

it('publishes the migrations file when confirmed', function () {
    Publisher::shouldReceive('publishableModels')
        ->andReturn(collect([new Post()]));

    artisan('publisher:install')
        ->expectsConfirmation('⚠️ Please ensure you have added the \Plank\Publisher\Contracts\Publishable interface to the models you wish to be publishable. Continue?', 'yes')
        ->expectsConfirmation('Would you like to publish the config file?', 'no')
        ->expectsConfirmation('Would you like to add the Publisher migrations?', 'yes')
        ->expectsConfirmation('Would you like to run the migrations now?', 'no')
        ->assertExitCode(0);

    $files = File::allFiles(database_path('migrations'));

    expect($files)->toHaveCount(1);
    expect($files[0]->getFilename())->toContain('add_publishable_fields_to_post_table');
});

it('doesnt publish the migrations file when not confirmed', function () {
    artisan('publisher:install')
        ->expectsConfirmation('⚠️ Please ensure you have added the \Plank\Publisher\Contracts\Publishable interface to the models you wish to be publishable. Continue?', 'yes')
        ->expectsConfirmation('Would you like to publish the config file?', 'no')
        ->expectsConfirmation('Would you like to add the Publisher migrations?', 'no')
        ->assertExitCode(0);

    $files = File::allFiles(database_path('migrations'));

    expect($files)->toBeEmpty();
});
