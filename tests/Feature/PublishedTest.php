<?php

use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

it('does not set has_been_published on publishable models when created in draft', function () {
    $post = Post::create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'draft',
    ]);

    $this->assertFalse($post->hasEverBeenPublished());
});

it('sets has_been_published on publishable models when created as published', function () {
    $post = Post::create([
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
    $post = Post::create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'published',
    ]);

    $this->assertEquals('published', $post->status);
    $this->assertTrue($post->hasEverBeenPublished());

    $post->update([
        'title' => 'My Updated Post',
        'status' => 'draft',
    ]);

    $this->assertEquals('draft', $post->status);
    $this->assertTrue($post->hasEverBeenPublished());
});
