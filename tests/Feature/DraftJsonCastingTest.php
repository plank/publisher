<?php

use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('casts json attributes when a model is created in draft', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'metadata' => ['key' => 'value', 'nested' => ['a' => 1]],
        'status' => 'draft',
    ]);

    expect($post->metadata)->toBe(['key' => 'value', 'nested' => ['a' => 1]]);
});

it('stores json attributes in draft column when a model is created in draft', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'metadata' => ['key' => 'value'],
        'status' => 'draft',
    ]);

    $raw = $post->getRawAttributes();
    $draft = json_decode($raw['draft'], true);

    expect($draft)->toHaveKey('metadata');
});

it('casts json attributes from draft after refreshing from the database', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'metadata' => ['key' => 'value', 'nested' => ['a' => 1]],
        'status' => 'draft',
    ]);

    $retrieved = Post::query()
        ->withoutGlobalScopes()
        ->find($post->id);

    expect($retrieved->metadata)->toBe(['key' => 'value', 'nested' => ['a' => 1]]);
});

it('casts updated json attributes from draft', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'metadata' => ['key' => 'value'],
        'status' => 'draft',
    ]);

    $post->update([
        'metadata' => ['key' => 'updated', 'new_key' => 'new_value'],
    ]);

    expect($post->metadata)->toBe(['key' => 'updated', 'new_key' => 'new_value']);

    $retrieved = Post::query()
        ->withoutGlobalScopes()
        ->find($post->id);

    expect($retrieved->metadata)->toBe(['key' => 'updated', 'new_key' => 'new_value']);
});

it('casts json attributes correctly after publishing', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'metadata' => ['key' => 'value'],
        'status' => 'draft',
    ]);

    $post->update([
        'metadata' => ['key' => 'updated'],
    ]);

    $post->status = 'published';
    $post->save();

    expect($post->metadata)->toBe(['key' => 'updated']);
    expect($post->draft)->toBeNull();

    $retrieved = Post::query()
        ->withoutGlobalScopes()
        ->find($post->id);

    expect($retrieved->metadata)->toBe(['key' => 'updated']);
});

it('handles null json attributes in draft', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'metadata' => null,
        'status' => 'draft',
    ]);

    expect($post->metadata)->toBeNull();

    $retrieved = Post::query()
        ->withoutGlobalScopes()
        ->find($post->id);

    expect($retrieved->metadata)->toBeNull();
});

it('casts json attributes with deeply nested structures from draft', function () {
    $metadata = [
        'seo' => [
            'title' => 'SEO Title',
            'keywords' => ['php', 'laravel'],
            'settings' => [
                'index' => true,
                'follow' => false,
            ],
        ],
        'flags' => [1, 2, 3],
    ];

    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'metadata' => $metadata,
        'status' => 'draft',
    ]);

    expect($post->metadata)->toBe($metadata);

    $retrieved = Post::query()
        ->withoutGlobalScopes()
        ->find($post->id);

    expect($retrieved->metadata)->toBe($metadata);
});

it('preserves published json attribute values when draft is updated', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'metadata' => ['key' => 'published_value'],
        'status' => 'draft',
    ]);

    $post->update([
        'metadata' => ['key' => 'draft_value'],
    ]);

    expect($post->metadata)->toBe(['key' => 'draft_value']);
    expect($post->getPublishedAttribute('metadata'))->toBe(['key' => 'published_value']);
});

it('returns all published attributes with properly cast json values', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'metadata' => ['key' => 'published_value'],
        'status' => 'draft',
    ]);

    $post->update([
        'metadata' => ['key' => 'draft_value'],
    ]);

    $published = $post->getPublishedAttributes();

    expect($published['metadata'])->toBe(['key' => 'published_value']);
});
