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

it('fires queuingForDelete event when dependent content is queued for deletion', function () {
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::DRAFT,
    ]);

    $queuingForDeleteFired = false;
    $modelDuringEvent = null;

    Section::queuingForDelete(function (Section $model) use (&$queuingForDeleteFired, &$modelDuringEvent) {
        $queuingForDeleteFired = true;
        $modelDuringEvent = $model;
    });

    $section->delete();

    expect($queuingForDeleteFired)->toBeTrue();
    expect($modelDuringEvent)->toBe($section);
    expect($section->should_delete)->toBeTrue();
});

it('fires queuedForDelete event after dependent content is queued for deletion', function () {
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::DRAFT,
    ]);

    $queuedForDeleteFired = false;
    $shouldDeleteDuringEvent = null;

    Section::queuedForDelete(function (Section $model) use (&$queuedForDeleteFired, &$shouldDeleteDuringEvent) {
        $queuedForDeleteFired = true;
        $shouldDeleteDuringEvent = $model->should_delete;
    });

    $section->delete();

    expect($queuedForDeleteFired)->toBeTrue();
    expect($shouldDeleteDuringEvent)->toBeTrue();
});

it('does not fire queuingForDelete or queuedForDelete events when parent is published', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    $queuingForDeleteFired = false;
    $queuedForDeleteFired = false;

    Section::queuingForDelete(function () use (&$queuingForDeleteFired) {
        $queuingForDeleteFired = true;
    });

    Section::queuedForDelete(function () use (&$queuedForDeleteFired) {
        $queuedForDeleteFired = true;
    });

    $section->delete();

    expect($queuingForDeleteFired)->toBeFalse();
    expect($queuedForDeleteFired)->toBeFalse();
});

it('restores dependent content queued for delete when parent is reverted', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post (sections become draft)
    $post->status = Status::DRAFT;
    $post->save();

    // Queue a section for deletion
    $section = $sections->first();
    $section->delete();

    expect($section->should_delete)->toBeTrue();
    expect($post->sections()->withoutGlobalScopes()->count())->toBe(3);
    expect($post->sections()->count())->toBe(2);

    // Revert the post
    $post->revert();

    // The section should no longer be queued for delete
    expect($section->fresh()->should_delete)->toBeFalse();
    expect($section->fresh()->status)->toBe(Status::PUBLISHED);
    expect($post->sections()->count())->toBe(3);
});

it('deletes dependent content that has never been published when parent is reverted', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $sections = Section::factory(2)->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    // Create a new section that has never been published
    $newSection = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::DRAFT,
    ]);

    expect($newSection->has_been_published)->toBeFalse();
    expect($post->sections()->withoutGlobalScopes()->count())->toBe(3);

    // Revert the post
    $post->revert();

    // The original sections should be published
    $sections->each(function ($section) {
        expect($section->fresh()->status)->toBe(Status::PUBLISHED);
    });

    // The new section should be deleted (never been published)
    expect($newSection->fresh())->toBeNull();
    expect($post->sections()->withoutGlobalScopes()->count())->toBe(2);
});
