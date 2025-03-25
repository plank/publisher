<?php

use Illuminate\Support\Facades\Gate;
use Plank\Publisher\Enums\Status;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

it('should not allow publishing when the publishing gate returns false', function () {
    // Rebind the view-draft-content gate
    Gate::define('publish', function ($user) {
        return false;
    });

    expect(fn () => Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => '(Published) My Second Post',
        'slug' => 'my-second-post',
        'body' => 'A second post.',
        'status' => 'published',
    ]))->toThrow('This action is unauthorized.');
});

it('should not allow unpublishing when the unpublishing gate returns false', function () {
    // Rebind the view-draft-content gate
    Gate::define('unpublish', function ($user) {
        return false;
    });

    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => '(Published) My Second Post',
        'slug' => 'my-second-post',
        'body' => 'A second post.',
        'status' => 'published',
    ]);

    $post->status = Status::unpublished();
    expect(fn () => $post->save())->toThrow('This action is unauthorized.');
});
