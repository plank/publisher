<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\Section;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('publishes all dependent content when its parent is published', function () {
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::DRAFT,
    ]);

    $post->status = Status::PUBLISHED;
    $post->save();

    $sections->each(function ($section) {
        expect($section->fresh()->status)->toBe(Status::PUBLISHED);
    });
});

it('unpublishes all dependent content when its parent is unpublished', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    $post->status = Status::DRAFT;
    $post->save();

    $sections->each(function ($section) {
        expect($section->fresh()->status)->toBe(Status::DRAFT);
    });
});

it('does not delete dependent content when its parent is in draft', function () {
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::DRAFT,
    ]);

    $section = $sections->first();
    $section->delete();
    expect($post->sections()->withoutGlobalScopes()->count())->toBe(3);
    expect($post->sections()->count())->toBe(2);
    expect($section->should_delete)->toBeTrue();
});

it('deletes dependent content when its parent is published', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    $section = $sections->first();
    $section->delete();
    expect($post->sections()->withoutQueuedDeletes()->count())->toBe(2);
    expect($post->sections()->count())->toBe(2);
    expect($section->should_delete)->toBeFalse();
});

it('deletes dependent content queued to be deleted when its parent is published', function () {
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::DRAFT,
    ]);

    $section = $sections->first();
    $section->delete();
    expect($post->sections()->withoutGlobalScopes()->count())->toBe(3);
    expect($post->sections()->count())->toBe(2);
    expect($section->should_delete)->toBeTrue();

    $post->status = Status::PUBLISHED;
    $post->save();

    expect($post->sections()->withoutGlobalScopes()->count())->toBe(2);
    expect($post->sections()->count())->toBe(2);
});

it('does not delete dependent content queued to be deleted when its parent is saved in a non-published state', function () {
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::DRAFT,
    ]);

    $section = $sections->first();
    $section->delete();

    expect($post->sections()->withoutGlobalScopes()->count())->toBe(3);
    expect($post->sections()->count())->toBe(2);
    expect($section->should_delete)->toBeTrue();

    $post->title .= ' – Updated';
    $post->save();

    expect($post->sections()->withoutGlobalScopes()->count())->toBe(3);
    expect($post->sections()->count())->toBe(2);
});
