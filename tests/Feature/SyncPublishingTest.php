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

it('queues dependent content for delete when its parent has been published but is in draft', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    $section = $sections->first();
    $section->delete();

    expect($post->sections()->withoutGlobalScopes()->count())->toBe(3);
    expect($post->sections()->count())->toBe(2);
    expect($section->should_delete)->toBeTrue();
});

it('deletes dependent content when its parent has never been published', function () {
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::DRAFT,
    ]);

    $section = $sections->first();
    $section->delete();

    expect($post->sections()->withoutGlobalScopes()->count())->toBe(2);
    expect($post->sections()->count())->toBe(2);
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
        'status' => Status::PUBLISHED,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    $section = $sections->first();
    $section->delete();

    expect($post->sections()->withoutGlobalScopes()->count())->toBe(3);
    expect($post->sections()->count())->toBe(2);
    expect($section->should_delete)->toBeTrue();

    // Re-publish the post
    $post->status = Status::PUBLISHED;
    $post->save();

    expect($post->sections()->withoutGlobalScopes()->count())->toBe(2);
    expect($post->sections()->count())->toBe(2);
});

it('does not delete dependent content queued to be deleted when its parent is saved in a non-published state', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $sections = Section::factory(3)->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

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

it('fires suspending event when dependent content is queued for deletion', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    $suspendingFired = false;
    $modelDuringEvent = null;

    Section::suspending(function (Section $model) use (&$suspendingFired, &$modelDuringEvent) {
        $suspendingFired = true;
        $modelDuringEvent = $model;
    });

    $section->refresh();
    $section->delete();

    expect($suspendingFired)->toBeTrue();
    expect($modelDuringEvent)->toBe($section);
    expect($section->should_delete)->toBeTrue();
});

it('fires suspended event after dependent content is queued for deletion', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    $suspendedFired = false;
    $shouldDeleteDuringEvent = null;

    Section::suspended(function (Section $model) use (&$suspendedFired, &$shouldDeleteDuringEvent) {
        $suspendedFired = true;
        $shouldDeleteDuringEvent = $model->should_delete;
    });

    $section->refresh();
    $section->delete();

    expect($suspendedFired)->toBeTrue();
    expect($shouldDeleteDuringEvent)->toBeTrue();
});

it('does not fire suspending or suspended events when parent is published', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    $suspendingFired = false;
    $suspendedFired = false;

    Section::suspending(function () use (&$suspendingFired) {
        $suspendingFired = true;
    });

    Section::suspended(function () use (&$suspendedFired) {
        $suspendedFired = true;
    });

    $section->delete();

    expect($suspendingFired)->toBeFalse();
    expect($suspendedFired)->toBeFalse();
});

it('does not fire suspending or suspended events when parent has never been published', function () {
    $post = Post::factory()->create([
        'status' => Status::DRAFT,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::DRAFT,
    ]);

    $suspendingFired = false;
    $suspendedFired = false;

    Section::suspending(function () use (&$suspendingFired) {
        $suspendingFired = true;
    });

    Section::suspended(function () use (&$suspendedFired) {
        $suspendedFired = true;
    });

    $section->delete();

    expect($suspendingFired)->toBeFalse();
    expect($suspendedFired)->toBeFalse();
    expect(Section::withoutGlobalScopes()->find($section->id))->toBeNull();
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

it('can suspend a dependent model directly', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    $section->refresh();

    expect($section->isSuspended())->toBeFalse();

    $section->suspend();

    expect($section->isSuspended())->toBeTrue();
    expect($section->should_delete)->toBeTrue();
});

it('fires suspending and suspended events when calling suspend()', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    $suspendingFired = false;
    $suspendedFired = false;

    Section::suspending(function () use (&$suspendingFired) {
        $suspendingFired = true;
    });

    Section::suspended(function () use (&$suspendedFired) {
        $suspendedFired = true;
    });

    $section->refresh();
    $section->suspend();

    expect($suspendingFired)->toBeTrue();
    expect($suspendedFired)->toBeTrue();
});

it('can resume a suspended dependent model directly', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    $section->refresh();
    $section->suspend();

    expect($section->isSuspended())->toBeTrue();

    $section->resume();

    expect($section->isSuspended())->toBeFalse();
    expect($section->should_delete)->toBeFalse();
});

it('fires resuming and resumed events when calling resume()', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    $section->refresh();
    $section->suspend();

    $resumingFired = false;
    $resumedFired = false;

    Section::resuming(function () use (&$resumingFired) {
        $resumingFired = true;
    });

    Section::resumed(function () use (&$resumedFired) {
        $resumedFired = true;
    });

    $section->resume();

    expect($resumingFired)->toBeTrue();
    expect($resumedFired)->toBeTrue();
});

it('fires resuming and resumed events when parent is reverted and dependent was suspended', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    // Queue section for deletion
    $section->refresh();
    $section->delete();

    expect($section->isSuspended())->toBeTrue();

    $resumingFired = false;
    $resumedFired = false;

    Section::resuming(function () use (&$resumingFired) {
        $resumingFired = true;
    });

    Section::resumed(function () use (&$resumedFired) {
        $resumedFired = true;
    });

    // Revert the post - should resume the section
    $post->revert();

    expect($resumingFired)->toBeTrue();
    expect($resumedFired)->toBeTrue();
    expect($section->fresh()->isSuspended())->toBeFalse();
});

it('does not fire resuming or resumed events when parent is reverted and dependent was not suspended', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    $section->refresh();

    expect($section->isSuspended())->toBeFalse();

    $resumingFired = false;
    $resumedFired = false;

    Section::resuming(function () use (&$resumingFired) {
        $resumingFired = true;
    });

    Section::resumed(function () use (&$resumedFired) {
        $resumedFired = true;
    });

    // Revert the post - should NOT fire resuming/resumed since section wasn't suspended
    $post->revert();

    expect($resumingFired)->toBeFalse();
    expect($resumedFired)->toBeFalse();
});

it('deletes suspended dependent when parent is published', function () {
    $post = Post::factory()->create([
        'status' => Status::PUBLISHED,
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'status' => Status::PUBLISHED,
    ]);

    // Unpublish the post
    $post->status = Status::DRAFT;
    $post->save();

    // Suspend the section directly
    $section->refresh();
    $section->suspend();

    expect($section->isSuspended())->toBeTrue();
    expect(Section::withoutGlobalScopes()->find($section->id))->not->toBeNull();

    // Re-publish the post - suspended section should be deleted
    $post->status = Status::PUBLISHED;
    $post->save();

    expect(Section::withoutGlobalScopes()->find($section->id))->toBeNull();
});
