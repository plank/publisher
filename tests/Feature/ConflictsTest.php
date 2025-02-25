<?php

use Illuminate\Support\Facades\Queue;
use Plank\Publisher\Enums\Status;
use Plank\Publisher\Jobs\ResolveSchemaConflicts;
use Plank\Publisher\Tests\Helpers\Models\Post;

use function Pest\Laravel\artisan;

it('handles conflicting changes to publishable model schemas', function () {
    Queue::fake();

    artisan('migrate', [
        '--path' => migrationPath('ConflictMigrations'),
        '--realpath' => true,
    ])->run();

    $jobs = Queue::pushedJobs()[ResolveSchemaConflicts::class];

    expect($jobs)->toHaveCount(5);

    expect($jobs[0]['job']->renamed)->toContain([
        'from' => 'teaser',
        'to' => 'blurb',
    ]);

    expect($jobs[1]['job']->dropped)->toContain('body');

    expect($jobs[2]['job']->dropped)->toContain('subtitle');

    expect($jobs[3]['job']->renamed)->toContain([
        'from' => 'body',
        'to' => 'message',
    ]);

    expect($jobs[4]['job']->dropped)->toContain('name');
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
