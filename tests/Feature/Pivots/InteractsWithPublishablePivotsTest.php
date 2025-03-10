<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\Tag;
use Plank\Publisher\Tests\Helpers\Models\User;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('queries published models correctly through pivots correctly while draft content is restricted', function () {
    /** @var User $user */
    $user = User::factory()->create();

    /** @var Tag $tag */
    $tag = Tag::factory()->create();

    /** @var Post $post */
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $post->editors()->attach([$user->id]);
    $post->tags()->attach([$tag->id]);

    expect($edited = $user->edited()->get())->toHaveCount(1);
    expect($posts = $tag->posts()->get())->toHaveCount(1);
});

it('queries draft models correctly through a pivot correctly while draft content is restricted', function () {
    /** @var User $user */
    $user = User::factory()->create();

    /** @var Tag $tag */
    $tag = Tag::factory()->create();

    /** @var Post $post */
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $post->editors()->attach([$user->id]);
    $post->tags()->attach([$tag->id]);

    expect($edited = $user->edited()->get())->toHaveCount(1);
    expect($posts = $tag->posts()->get())->toHaveCount(1);
});

it('queries published models correctly through pivots correctly while draft content is allowed', function () {
    /** @var User $user */
    $user = User::factory()->create();

    /** @var Tag $tag */
    $tag = Tag::factory()->create();

    /** @var Post $post */
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $post->editors()->attach([$user->id]);
    $post->tags()->attach([$tag->id]);

    expect($edited = $user->edited()->get())->toHaveCount(1);
    expect($posts = $tag->posts()->get())->toHaveCount(1);
});

it('queries draft models correctly through a pivot correctly while draft content is allowed', function () {
    /** @var User $user */
    $user = User::factory()->create();

    /** @var Tag $tag */
    $tag = Tag::factory()->create();

    /** @var Post $post */
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $post->editors()->attach([$user->id]);
    $post->tags()->attach([$tag->id]);

    Publisher::withDraftContent(function () use ($user, $tag) {
        expect($edited = $user->edited()->get())->toHaveCount(1);
        expect($posts = $tag->posts()->get())->toHaveCount(1);
    });
});
