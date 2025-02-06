<?php

use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

it('filters results to models that are only in draft', function () {
    $draft = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => '(Published) My First Post',
        'slug' => 'my-first-post',
        'body' => 'A first post.',
        'status' => 'published',
    ]);

    $draft->title = '(Draft) My First Post';
    $draft->status = 'draft';
    $draft->save();

    Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => '(Published) My Second Post',
        'slug' => 'my-second-post',
        'body' => 'A second post.',
        'status' => 'published',
    ]);

    $results = Post::query()
        ->onlyDraft()
        ->get();

    expect($results)->toHaveCount(1);
});
