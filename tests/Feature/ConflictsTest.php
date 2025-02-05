<?php

use Illuminate\Support\Facades\Queue;
use Plank\Publisher\Enums\ConflictType;
use Plank\Publisher\Enums\Status;
use Plank\Publisher\Jobs\ResolveSchemaConflicts;
use Plank\Publisher\Tests\Helpers\Models\Post;

use function Pest\Laravel\artisan;

it('handles conflicting changes to publishable model schemas without any models to heal', function () {
    Queue::fake();

    artisan('migrate', [
        '--path' => migrationPath('ConflictMigrations'),
        '--realpath' => true,
    ])->run();

    $jobs = Queue::pushedJobs()[ResolveSchemaConflicts::class];

    expect($jobs)->toHaveCount(1);
    expect($job = $jobs[0]['job'])->toBeInstanceOf(ResolveSchemaConflicts::class);
    expect($conflicts = $job->conflicts)->toHaveCount(3);
    expect($conflicts[0]->type)->toBe(ConflictType::Renamed);
    expect($conflicts[1]->type)->toBe(ConflictType::Dropped);
    expect($conflicts[2]->type)->toBe(ConflictType::Dropped);
});

it('resolves conflicts to publishable models correctly', function () {
    Post::factory()
        ->create([
            'title' => 'No Fixing Necessary',
            'status' => Status::PUBLISHED,
        ]);

    $toFix = Post::factory()
        ->create([
            'title' => 'Needs fixing',
            'teaser' => 'Original Teaser',
            'status' => Status::PUBLISHED,
        ]);

    $toFix->teaser = 'Draft Teaser';
    $toFix->status = Status::DRAFT;
    $toFix->save();

    artisan('migrate', [
        '--path' => migrationPath('ConflictMigrations'),
        '--realpath' => true,
    ])->run();

    $fixed = Post::query()->find($toFix->getKey());
    expect($fixed->draft)->not->toHaveKey('body');
    expect($fixed->draft)->not->toHaveKey('subtitle');
    expect($fixed->draft)->not->toHaveKey('teaser');
    expect($fixed->draft)->toHaveKey('blurb');
    expect($fixed->draft['blurb'])->toBe('Draft Teaser');
    expect($fixed->blurb)->toBe('Original Teaser');

    $fixed->status = Status::PUBLISHED;
    expect($fixed->save())->toBe(true);
});
