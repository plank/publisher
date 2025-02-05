<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Exceptions\RevertException;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

it('does not set has_been_published on publishable models when created in draft', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'draft',
    ]);

    $this->assertFalse($post->hasEverBeenPublished());
});

it('sets has_been_published on publishable models when created as published', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'published',
    ]);

    $this->assertTrue($post->hasEverBeenPublished());
});

it('maintains has_been_published state after unpublishing', function () {
    /** @var Post $post */
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => Status::published(),
    ]);

    $this->assertEquals(Status::published(), $post->status);
    $this->assertTrue($post->hasEverBeenPublished());

    $post->update([
        'title' => 'My Updated Post',
        'status' => 'draft',
    ]);

    $this->assertEquals(Status::unpublished(), $post->status);
    $this->assertTrue($post->hasEverBeenPublished());
});

it('allows revert for content that has been published', function () {
    /** @var Post $post */
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => Status::published(),
    ]);

    $this->assertEquals(Status::published(), $post->status);
    $this->assertTrue($post->hasEverBeenPublished());

    $post->update([
        'status' => 'draft',
    ]);

    $post->update([
        'title' => 'My Updated Post',
    ]);

    $this->assertEquals(Status::unpublished(), $post->status);
    $this->assertTrue($post->hasEverBeenPublished());

    $post->revert();

    $this->assertEquals(Status::published(), $post->status);
    $this->assertNull($post->draft);
    $this->assertEquals('My First Post', $post->title);
});

it('does nothing for revert when content has never been published', function () {
    /** @var Post $post */
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => Status::unpublished(),
    ]);

    $this->assertFalse($post->hasEverBeenPublished());
    $post->revert();
})->throws(RevertException::class);
