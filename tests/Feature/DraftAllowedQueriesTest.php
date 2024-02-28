<?php

use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('correctly filters results based on workflow state', function () {
    $draft = Post::create([
        'author_id' => User::first()->id,
        'title' => '(Published) My First Post',
        'slug' => 'my-first-post',
        'body' => 'A first post.',
        'status' => 'published',
    ]);

    $draft->title = '(Draft) My First Post';
    $draft->status = 'draft';
    $draft->save();

    Post::create([
        'author_id' => User::first()->id,
        'title' => '(Published) My Second Post',
        'slug' => 'my-second-post',
        'body' => 'A second post.',
        'status' => 'published',
    ]);

    $results = Post::query()
        ->where('title', '(Published) My First Post')
        ->get();

    expect($results)->toBeEmpty();

    $results = Post::query()
        ->where('title', '(Draft) My First Post')
        ->get();

    expect($results)->toHaveCount(1);

    $results = Post::query()
        ->where('title', '(Published) My Second Post')
        ->get();

    expect($results)->toHaveCount(1);

    $results = Post::query()
        ->where('title', 'like', '(Published)%')
        ->get();

    expect($results)->toHaveCount(1);

    $results = Post::query()
        ->where('title', 'like', '(Draft)%')
        ->get();

    expect($results)->toHaveCount(1);
});
