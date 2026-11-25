<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;

it('eager loads a publishable belongsToMany when the related model is drafted', function () {
    Publisher::allowDraftContent();

    $post = Post::factory()->create(['status' => Status::PUBLISHED]);
    $featured = Post::factory()->create(['status' => Status::PUBLISHED]);

    $post->featured()->attach([$featured->getKey()]);

    $featured->status = Status::DRAFT;
    $featured->save();

    $lazy = Post::find($post->getKey())->featured;
    $eager = Post::with('featured')->find($post->getKey())->featured;

    $loaded = Post::find($post->getKey());
    $loaded->load('featured');

    expect($lazy->pluck('id')->toArray())->toBe([$featured->id]);
    expect($eager->pluck('id')->toArray())->toBe($lazy->pluck('id')->toArray());
    expect($loaded->featured->pluck('id')->toArray())->toBe($lazy->pluck('id')->toArray());
});

it('still excludes pivots queued for delete when the related model is drafted', function () {
    Publisher::allowDraftContent();

    $post = Post::factory()->create(['status' => Status::PUBLISHED]);
    $kept = Post::factory()->create(['status' => Status::PUBLISHED]);
    $detached = Post::factory()->create(['status' => Status::PUBLISHED]);

    $post->featured()->attach([$kept->getKey(), $detached->getKey()]);

    $post->status = Status::DRAFT;
    $post->save();

    $post->featured()->detach([$detached->getKey()]);

    $detached->status = Status::DRAFT;
    $detached->save();

    $eager = Post::with('featured')->find($post->getKey())->featured;

    expect($eager->pluck('id')->toArray())->toBe([$kept->id]);
});

it('excludes never published pivots when draft content is restricted', function () {
    Publisher::allowDraftContent();

    $post = Post::factory()->create(['status' => Status::PUBLISHED]);
    $featured = Post::factory()->create(['status' => Status::PUBLISHED]);

    $post->status = Status::DRAFT;
    $post->save();

    $post->featured()->attach([$featured->getKey()]);

    Publisher::restrictDraftContent();

    expect(Post::with('featured')->find($post->getKey())->featured)->toHaveCount(0);
});
