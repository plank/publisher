<?php

use Illuminate\Support\Facades\Gate;
use Plank\Publisher\Enums\Status;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

use function Pest\Laravel\get;

beforeEach(function () {
    $draft = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => '(Published) My First Post',
        'slug' => 'my-first-post',
        'body' => 'A first post.',
        'status' => Status::PUBLISHED,
    ]);

    $draft->title = '(Draft) My First Post';
    $draft->status = 'draft';
    $draft->save();
});

it('retrieves model attributes when not using the previewKey via middleware', function () {
    $response = get('/posts/1');
    $response->assertStatus(200);

    $post = $response->original;

    expect($post->title)->toBe('(Published) My First Post');
});

it('retrieves draft attributes when using the previewKey via middleware', function () {
    $response = get('/posts/1?preview=true');
    $response->assertStatus(200);

    $post = $response->original;

    expect($post->title)->toBe('(Draft) My First Post');
});

it('retrieves model attributes when the user is not permitted to view-draft-content', function () {
    Gate::define('view-draft-content', function ($user) {
        return false;
    });

    $response = get('/posts/1?preview=true');
    $response->assertStatus(200);

    $post = $response->original;

    expect($post->title)->toBe('(Published) My First Post');
});
