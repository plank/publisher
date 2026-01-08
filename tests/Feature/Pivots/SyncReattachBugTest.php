<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Support\PivotEventTracker;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('sync reattaches a previously draft-detached pivot on a never-published parent', function () {
    // Parent that has NEVER been published
    $post = Post::factory()->create(['status' => Status::DRAFT]);
    $featured = Post::factory()->create();

    // Step 1: Draft attach
    $post->featured()->attach([$featured->getKey()]);

    // Step 2: Draft detach (marks for deletion)
    $post->featured()->detach([$featured->getKey()]);

    // Verify pivot is marked for deletion
    $pivot = \Illuminate\Support\Facades\DB::table('post_post')
        ->where('post_id', $post->getKey())
        ->where('featured_id', $featured->getKey())
        ->first();
    expect((bool) $pivot->should_delete)->toBeTrue();
    expect((bool) $pivot->has_been_published)->toBeFalse();

    // Step 3: Sync (should reattach, not draft attach again)
    $tracker = PivotEventTracker::make();
    $result = $post->featured()->sync([$featured->getKey()]);

    // Sync only fires aggregate syncing/synced events (not intermediate attach/detach/reattach events)
    expect($tracker->firedEvents)->toBe(['pivotDraftSyncing', 'pivotDraftSynced']);

    // Verify sync result correctly reports reattached, not draftAttached
    expect($result['reattached'])->toContain($featured->getKey());
    expect($result['draftAttached'])->toBeEmpty();

    // Verify pivot state is correct
    $pivot = \Illuminate\Support\Facades\DB::table('post_post')
        ->where('post_id', $post->getKey())
        ->where('featured_id', $featured->getKey())
        ->first();
    expect((bool) $pivot->should_delete)->toBeFalse();
});
