<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Media;
use Plank\Publisher\Tests\Helpers\Models\Post;

beforeEach(function () {
    Publisher::allowDraftContent();
});

describe('Pivot Draft Attach Events', function () {
    it('fires pivotDraftAttaching event when attaching to a draft parent that has been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDraftAttaching(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'relation' => $relation,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->attach([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('fires pivotDraftAttached event after attaching to a draft parent that has been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDraftAttached(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'relation' => $relation,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->attach([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('does not fire pivotDraftAttaching or pivotDraftAttached events when parent is published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();

        $attachingFired = false;
        $attachedFired = false;

        Post::pivotDraftAttaching(fn () => $attachingFired = true);
        Post::pivotDraftAttached(fn () => $attachedFired = true);

        $post->featured()->attach([$featured->getKey()]);

        expect($attachingFired)->toBeFalse();
        expect($attachedFired)->toBeFalse();
    });

    it('does not fire pivotDraftAttaching or pivotDraftAttached events when parent has never been published', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        $attachingFired = false;
        $attachedFired = false;

        Post::pivotDraftAttaching(fn () => $attachingFired = true);
        Post::pivotDraftAttached(fn () => $attachedFired = true);

        $post->featured()->attach([$featured->getKey()]);

        expect($attachingFired)->toBeFalse();
        expect($attachedFired)->toBeFalse();
    });

    it('receives pivot attributes in the pivotDraftAttaching event', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $eventPayload = null;

        Post::pivotDraftAttaching(function ($model, $relation, $pivotIds, $pivotIdsAttributes) use (&$eventPayload) {
            $eventPayload = [
                'pivotIdsAttributes' => $pivotIdsAttributes,
            ];
        });

        $post->featured()->attach([$featured->getKey() => ['paywall' => true]]);

        expect($eventPayload['pivotIdsAttributes'])->toHaveKey($featured->getKey());
        expect($eventPayload['pivotIdsAttributes'][$featured->getKey()])->toBe(['paywall' => true]);
    });

    it('receives multiple ids in the pivotDraftAttaching event', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured1 = Post::factory()->create();
        $featured2 = Post::factory()->create();

        $eventPayload = null;

        Post::pivotDraftAttaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        $post->featured()->attach([$featured1->getKey(), $featured2->getKey()]);

        expect($eventPayload['pivotIds'])->toContain($featured1->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured2->getKey());
    });
});

describe('Pivot Draft Detach Events', function () {
    it('fires pivotDraftDetaching event when detaching from a draft parent that has been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDraftDetaching(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->detach([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('fires pivotDraftDetached event after detaching from a draft parent that has been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDraftDetached(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->detach([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('does not fire pivotDraftDetaching or pivotDraftDetached events when parent is published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $detachingFired = false;
        $detachedFired = false;

        Post::pivotDraftDetaching(fn () => $detachingFired = true);
        Post::pivotDraftDetached(fn () => $detachedFired = true);

        $post->featured()->detach([$featured->getKey()]);

        expect($detachingFired)->toBeFalse();
        expect($detachedFired)->toBeFalse();
    });

    it('does not fire pivotDraftDetaching or pivotDraftDetached events when parent has never been published', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $detachingFired = false;
        $detachedFired = false;

        Post::pivotDraftDetaching(fn () => $detachingFired = true);
        Post::pivotDraftDetached(fn () => $detachedFired = true);

        $post->featured()->detach([$featured->getKey()]);

        expect($detachingFired)->toBeFalse();
        expect($detachedFired)->toBeFalse();
    });

    it('fires pivotDraftDetaching when detaching all pivots without specifying ids', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured1 = Post::factory()->create();
        $featured2 = Post::factory()->create();
        $post->featured()->attach([$featured1->getKey(), $featured2->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $eventPayload = null;

        Post::pivotDraftDetaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = [
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->detach();

        expect($eventPayload['pivotIds'])->toContain($featured1->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured2->getKey());
    });
});

describe('Pivot Draft Sync Events', function () {
    it('fires pivotDraftSyncing event when syncing on a draft parent that has been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDraftSyncing(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->sync([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('fires pivotDraftSynced event after syncing on a draft parent that has been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDraftSynced(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->sync([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('does not fire pivotDraftSyncing or pivotDraftSynced events when parent is published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();

        $syncingFired = false;
        $syncedFired = false;

        Post::pivotDraftSyncing(fn () => $syncingFired = true);
        Post::pivotDraftSynced(fn () => $syncedFired = true);

        $post->featured()->sync([$featured->getKey()]);

        expect($syncingFired)->toBeFalse();
        expect($syncedFired)->toBeFalse();
    });

    it('does not fire pivotDraftSyncing or pivotDraftSynced events when parent has never been published', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        $syncingFired = false;
        $syncedFired = false;

        Post::pivotDraftSyncing(fn () => $syncingFired = true);
        Post::pivotDraftSynced(fn () => $syncedFired = true);

        $post->featured()->sync([$featured->getKey()]);

        expect($syncingFired)->toBeFalse();
        expect($syncedFired)->toBeFalse();
    });

    it('receives pivot attributes in the pivotDraftSyncing event', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $eventPayload = null;

        Post::pivotDraftSyncing(function ($model, $relation, $pivotIds, $pivotIdsAttributes) use (&$eventPayload) {
            $eventPayload = [
                'pivotIdsAttributes' => $pivotIdsAttributes,
            ];
        });

        $post->featured()->sync([$featured->getKey() => ['paywall' => true]]);

        expect($eventPayload['pivotIdsAttributes'])->toHaveKey($featured->getKey());
        expect($eventPayload['pivotIdsAttributes'][$featured->getKey()])->toBe(['paywall' => true]);
    });
});

describe('Pivot Reattach Events', function () {
    it('fires pivotReattaching event when reattaching pivots marked for deletion', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark pivot for deletion
        $post->featured()->detach([$featured->getKey()]);

        $eventFired = false;
        $eventPayload = null;

        Post::pivotReattaching(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->reattach([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('fires pivotReattached event after reattaching pivots marked for deletion', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark pivot for deletion
        $post->featured()->detach([$featured->getKey()]);

        $eventFired = false;
        $eventPayload = null;

        Post::pivotReattached(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->reattach([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('reattach only affects pivots that have been published and are marked for deletion', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark pivot for deletion
        $post->featured()->detach([$featured->getKey()]);

        // Attach a new featured post that has never been published
        $newFeatured = Post::factory()->create();
        $post->featured()->attach([$newFeatured->getKey()]);

        $eventPayload = null;

        Post::pivotReattaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        // Reattach without specifying IDs - should only get published ones that are marked for deletion
        $post->featured()->reattach();

        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
        expect($eventPayload['pivotIds'])->not->toContain($newFeatured->getKey());
    });

    it('restores the pivot to visible state after reattach', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark pivot for deletion - it should no longer be visible
        $post->featured()->detach([$featured->getKey()]);
        expect($post->featured()->get())->toBeEmpty();

        // Reattach - it should become visible again
        $post->featured()->reattach([$featured->getKey()]);
        expect($post->featured()->count())->toBe(1);
    });
});

describe('Pivot Discard Events', function () {
    it('fires pivotDiscarding event when discarding unpublished pivots', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        // Attach a featured post while in draft - it will be unpublished
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDiscarding(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->discard([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('fires pivotDiscarded event after discarding unpublished pivots', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        // Attach a featured post while in draft - it will be unpublished
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDiscarded(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->discard([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('discard only affects pivots that have never been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Attach a new featured post in draft mode (never published)
        $newFeatured = Post::factory()->create();
        $post->featured()->attach([$newFeatured->getKey()]);

        $eventPayload = null;

        Post::pivotDiscarding(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        // Discard without specifying IDs - should only get unpublished ones
        $post->featured()->discard();

        expect($eventPayload['pivotIds'])->toContain($newFeatured->getKey());
        expect($eventPayload['pivotIds'])->not->toContain($featured->getKey());
    });

    it('permanently removes the pivot after discard', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        expect($post->featured()->count())->toBe(1);

        $post->featured()->discard([$featured->getKey()]);

        // The pivot should be completely gone
        expect($post->featured()->count())->toBe(0);
    });
});

describe('Pivot Publish Events', function () {
    it('fires pivotAttaching event when publishing unpublished pivots', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        // Attach a featured post while in draft - it will be unpublished
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $eventFired = false;
        $eventPayload = null;

        Post::pivotAttaching(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->publish([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('fires pivotAttached event after publishing unpublished pivots', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        // Attach a featured post while in draft - it will be unpublished
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $eventFired = false;
        $eventPayload = null;

        Post::pivotAttached(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->publish([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('publish only affects pivots that have never been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Attach a new featured post in draft mode (never published)
        $newFeatured = Post::factory()->create();
        $post->featured()->attach([$newFeatured->getKey()]);

        $eventPayload = null;

        Post::pivotAttaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        // Publish without specifying IDs - should only get unpublished ones
        $post->featured()->publish();

        expect($eventPayload['pivotIds'])->toContain($newFeatured->getKey());
        expect($eventPayload['pivotIds'])->not->toContain($featured->getKey());
    });

    it('marks the pivot as published after publish', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        // Check it's not published initially
        $pivot = $post->featured()->withPivot(['has_been_published'])->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();

        $post->featured()->publish([$featured->getKey()]);

        // Now it should be published
        $pivot = $post->featured()->withPivot(['has_been_published'])->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
    });
});

describe('Pivot Flush Events', function () {
    it('fires pivotDetaching event when flushing pivots marked for deletion', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark pivot for deletion
        $post->featured()->detach([$featured->getKey()]);

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDetaching(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->flush([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('fires pivotDetached event after flushing pivots marked for deletion', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark pivot for deletion
        $post->featured()->detach([$featured->getKey()]);

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDetached(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->flush([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('flush only affects pivots that are marked for deletion', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured1 = Post::factory()->create();
        $featured2 = Post::factory()->create();
        $post->featured()->attach([$featured1->getKey(), $featured2->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark only one pivot for deletion
        $post->featured()->detach([$featured1->getKey()]);

        $eventPayload = null;

        Post::pivotDetaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        // Flush without specifying IDs - should only get ones marked for deletion
        $post->featured()->flush();

        expect($eventPayload['pivotIds'])->toContain($featured1->getKey());
        expect($eventPayload['pivotIds'])->not->toContain($featured2->getKey());
    });

    it('permanently removes the pivot after flush', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark pivot for deletion
        $post->featured()->detach([$featured->getKey()]);

        // Flush should permanently delete it
        $post->featured()->flush([$featured->getKey()]);

        // The pivot should be completely gone, even without global scopes
        $count = \Illuminate\Support\Facades\DB::table('post_post')
            ->where('post_id', $post->getKey())
            ->where('featured_id', $featured->getKey())
            ->count();

        expect($count)->toBe(0);
    });
});

describe('Pivot Events with MorphToMany relations', function () {
    it('fires pivotDraftAttaching event on morphToMany relations', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $media = Media::factory()->create();

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDraftAttaching(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        $post->media()->attach([$media->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['pivotIds'])->toContain($media->getKey());
    });

    it('fires pivotDraftDetaching event on morphToMany relations', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $media = Media::factory()->create();
        $post->media()->attach([$media->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDraftDetaching(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        $post->media()->detach([$media->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['pivotIds'])->toContain($media->getKey());
    });

    it('fires pivotDraftSyncing event on morphToMany relations', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $media = Media::factory()->create();

        $eventFired = false;
        $eventPayload = null;

        Post::pivotDraftSyncing(function ($model, $relation, $pivotIds) use (&$eventFired, &$eventPayload) {
            $eventFired = true;
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        $post->media()->sync([$media->getKey()]);

        expect($eventFired)->toBeTrue();
        expect($eventPayload['pivotIds'])->toContain($media->getKey());
    });

    it('does not fire draft events on morphToMany when parent is published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $media = Media::factory()->create();

        $draftAttachingFired = false;

        Post::pivotDraftAttaching(fn () => $draftAttachingFired = true);

        $post->media()->attach([$media->getKey()]);

        expect($draftAttachingFired)->toBeFalse();
    });
});

describe('Pivot Events with custom pivot classes', function () {
    it('fires pivotDraftAttaching event with custom pivot class on BelongsToMany', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $eventFired = false;

        Post::pivotDraftAttaching(function () use (&$eventFired) {
            $eventFired = true;
        });

        $post->customFeatured()->attach([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
    });

    it('fires pivotDraftDetaching event with custom pivot class on BelongsToMany', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->customFeatured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $eventFired = false;

        Post::pivotDraftDetaching(function () use (&$eventFired) {
            $eventFired = true;
        });

        $post->customFeatured()->detach([$featured->getKey()]);

        expect($eventFired)->toBeTrue();
    });

    it('fires pivotDraftAttaching event with custom pivot class on MorphToMany', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $media = Media::factory()->create();

        $eventFired = false;

        Post::pivotDraftAttaching(function () use (&$eventFired) {
            $eventFired = true;
        });

        $post->customMedia()->attach([$media->getKey()]);

        expect($eventFired)->toBeTrue();
    });
});

describe('Pivot Event Cancellation', function () {
    it('can cancel pivotDraftAttaching and prevent the attach', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        Post::pivotDraftAttaching(function () {
            return false;
        });

        $result = $post->featured()->attach([$featured->getKey()]);

        expect($result)->toBeFalse();
        expect($post->featured()->count())->toBe(0);
    });

    it('can cancel pivotDraftDetaching and prevent the detach', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        Post::pivotDraftDetaching(function () {
            return false;
        });

        $result = $post->featured()->detach([$featured->getKey()]);

        expect($result)->toBeFalse();
        // The pivot should still be visible (not marked for deletion)
        expect($post->featured()->count())->toBe(1);
    });

    it('can cancel pivotDraftSyncing and prevent the sync', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        Post::pivotDraftSyncing(function () {
            return false;
        });

        $result = $post->featured()->sync([$featured->getKey()]);

        expect($result)->toBeFalse();
        expect($post->featured()->count())->toBe(0);
    });
});
