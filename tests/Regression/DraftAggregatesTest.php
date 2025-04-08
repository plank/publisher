<?php

use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('doesnt include withCount columns in draft by default', function () {
    Post::factory()
        ->hasComments($numberOfComments = fake()->numberBetween(0, 3))
        ->create([
            'author_id' => User::first()->id,
            'status' => 'published',
        ]);

    $post = Post::query()
        ->withCount('comments')
        ->first();

    expect($post->comments_count)->toBe($numberOfComments);
    expect($post->draft)->toBeNull();

    $post->status = 'draft';
    $post->save();

    expect($post->comments_count)->toBe($numberOfComments);
    expect($post->draft)->toHaveKey('title');
    expect($post->draft)->not->toHaveKey('comments_count');
});

it('includes withCount columns in draft when ignoreCounts is explicitly set to false', function () {
    Post::factory()
        ->hasComments($numberOfComments = fake()->numberBetween(0, 3))
        ->create([
            'author_id' => User::first()->id,
            'status' => 'published',
        ]);

    $ignores = new class extends Post
    {
        protected $table = 'posts';

        protected bool $ignoreCounts = false;
    };

    $post = $ignores->query()
        ->withCount('comments')
        ->first();

    expect($post->comments_count)->toBe($numberOfComments);
    expect($post->draft)->toBeNull();

    $post->status = 'draft';
    $post->save();

    expect($post->comments_count)->toBe($numberOfComments);
    expect($post->draft)->toHaveKey('title');
    expect($post->draft)->toHaveKey('comments_count');
});

it('doesnt include withSum columns in draft by default', function () {
    Post::factory()
        ->hasComments($numberOfComments = fake()->numberBetween(0, 3), [
            'upvotes' => $numberOfUpvotes = fake()->numberBetween(0, 3),
        ])
        ->create([
            'author_id' => User::first()->id,
            'status' => 'published',
        ]);

    $post = Post::query()
        ->withSum('comments', 'upvotes')
        ->first();

    expect($post->comments_sum_upvotes)->toBe($numberOfComments * $numberOfUpvotes);
    expect($post->draft)->toBeNull();

    $post->status = 'draft';
    $post->save();

    expect($post->comments_sum_upvotes)->toBe($numberOfComments * $numberOfUpvotes);
    expect($post->draft)->toHaveKey('title');
    expect($post->draft)->not->toHaveKey('comments_sum_upvotes');
});

it('includes withSum columns in draft when ignoreSums is explicitly set to false', function () {
    Post::factory()
        ->hasComments($numberOfComments = fake()->numberBetween(0, 3), [
            'upvotes' => $numberOfUpvotes = fake()->numberBetween(0, 3),
        ])
        ->create([
            'author_id' => User::first()->id,
            'status' => 'published',
        ]);

    $ignores = new class extends Post
    {
        protected $table = 'posts';

        protected bool $ignoreSums = false;
    };

    $post = $ignores->query()
        ->withSum('comments', 'upvotes')
        ->first();

    expect($post->comments_sum_upvotes)->toBe($numberOfComments * $numberOfUpvotes);
    expect($post->draft)->toBeNull();

    $post->status = 'draft';
    $post->save();

    expect($post->comments_sum_upvotes)->toBe($numberOfComments * $numberOfUpvotes);
    expect($post->draft)->toHaveKey('title');
    expect($post->draft)->toHaveKey('comments_sum_upvotes');
});
