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

it('eager loads drafted models through a morph pivot the same as lazy loading while draft content is allowed', function () {
    /** @var Tag $tag */
    $tag = Tag::factory()->create();

    /** @var Post $post */
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $post->tags()->attach([$tag->id]);

    $post->status = Status::DRAFT;
    $post->save();

    expect(Tag::find($tag->id)->posts->pluck('id')->all())->toBe([$post->id]);
    expect(Tag::with('posts')->find($tag->id)->posts->pluck('id')->all())->toBe([$post->id]);
});

it('finds drafted models through a morph pivot in existence queries while draft content is allowed', function () {
    /** @var Tag $tag */
    $tag = Tag::factory()->create();

    /** @var Post $post */
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $post->tags()->attach([$tag->id]);

    $post->status = Status::DRAFT;
    $post->save();

    expect(Tag::whereHas('posts')->pluck('id')->all())->toBe([$tag->id]);
    expect(Tag::withCount('posts')->find($tag->id)->posts_count)->toBe(1);
});
