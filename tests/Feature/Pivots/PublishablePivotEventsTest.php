<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Media;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Support\PivotEventTracker;

beforeEach(function () {
    Publisher::allowDraftContent();
});

describe('Pivot Draft Attach Events', function () {
    it('fires only pivotDraftAttaching and pivotDraftAttached when attaching to a draft parent that has been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->featured()->attach([$featured->getKey()]);

        $tracker->assertOnly(['pivotDraftAttaching', 'pivotDraftAttached']);
    });

    it('fires only pivotAttaching and pivotAttached when attaching to a published parent', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->featured()->attach([$featured->getKey()]);

        $tracker->assertOnly(['pivotAttaching', 'pivotAttached']);
    });

    it('fires only pivotDraftAttaching and pivotDraftAttached when attaching to a parent that has never been published', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->featured()->attach([$featured->getKey()]);

        $tracker->assertOnly(['pivotDraftAttaching', 'pivotDraftAttached']);
    });

    it('receives correct payload in pivotDraftAttaching event', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $eventPayload = null;

        Post::pivotDraftAttaching(function ($model, $relation, $pivotIds, $pivotIdsAttributes) use (&$eventPayload) {
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
                'pivotIdsAttributes' => $pivotIdsAttributes,
            ];
        });

        $post->featured()->attach([$featured->getKey() => ['paywall' => true]]);

        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
        expect($eventPayload['pivotIdsAttributes'])->toHaveKey($featured->getKey());
        expect($eventPayload['pivotIdsAttributes'][$featured->getKey()])->toBe(['paywall' => true]);
    });

    it('receives multiple ids in the pivotDraftAttaching event', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured1 = Post::factory()->create();
        $featured2 = Post::factory()->create();

        $tracker = PivotEventTracker::make();
        $eventPayload = null;

        Post::pivotDraftAttaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        $post->featured()->attach([$featured1->getKey(), $featured2->getKey()]);

        $tracker->assertOnly(['pivotDraftAttaching', 'pivotDraftAttached']);
        expect($eventPayload['pivotIds'])->toContain($featured1->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured2->getKey());
    });
});

describe('Pivot Draft Detach Events', function () {
    it('fires only pivotDraftDetaching and pivotDraftDetached when detaching from a draft parent that has been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $tracker = PivotEventTracker::make();

        $post->featured()->detach([$featured->getKey()]);

        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);
    });

    it('fires only pivotDetaching and pivotDetached when detaching from a published parent', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $tracker = PivotEventTracker::make();

        $post->featured()->detach([$featured->getKey()]);

        $tracker->assertOnly(['pivotDetaching', 'pivotDetached']);
    });

    it('fires only pivotDraftDetaching and pivotDraftDetached when detaching from a parent that has never been published', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $tracker = PivotEventTracker::make();

        $post->featured()->detach([$featured->getKey()]);

        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);
    });

    it('receives correct payload in pivotDraftDetaching event', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $eventPayload = null;

        Post::pivotDraftDetaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->detach([$featured->getKey()]);

        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
    });

    it('fires pivotDraftDetaching with all ids when detaching all pivots', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured1 = Post::factory()->create();
        $featured2 = Post::factory()->create();
        $post->featured()->attach([$featured1->getKey(), $featured2->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $tracker = PivotEventTracker::make();
        $eventPayload = null;

        Post::pivotDraftDetaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = ['pivotIds' => $pivotIds];
        });

        $post->featured()->detach();

        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);
        expect($eventPayload['pivotIds'])->toContain($featured1->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured2->getKey());
    });
});

describe('Pivot Draft Sync Events', function () {
    it('fires only pivotDraftSyncing and pivotDraftSynced when syncing on a draft parent that has been published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->featured()->sync([$featured->getKey()]);

        $tracker->assertOnly(['pivotDraftSyncing', 'pivotDraftSynced']);
    });

    it('fires only pivotSyncing and pivotSynced when syncing on a published parent', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->featured()->sync([$featured->getKey()]);

        $tracker->assertOnly(['pivotSyncing', 'pivotSynced']);
    });

    it('fires only pivotDraftSyncing and pivotDraftSynced when syncing on a parent that has never been published', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->featured()->sync([$featured->getKey()]);

        $tracker->assertOnly(['pivotDraftSyncing', 'pivotDraftSynced']);
    });

    it('receives correct payload in pivotDraftSyncing event', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $eventPayload = null;

        Post::pivotDraftSyncing(function ($model, $relation, $pivotIds, $pivotIdsAttributes) use (&$eventPayload) {
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
                'pivotIdsAttributes' => $pivotIdsAttributes,
            ];
        });

        $post->featured()->sync([$featured->getKey() => ['paywall' => true]]);

        expect($eventPayload['model']->getKey())->toBe($post->getKey());
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
        expect($eventPayload['pivotIdsAttributes'])->toHaveKey($featured->getKey());
        expect($eventPayload['pivotIdsAttributes'][$featured->getKey()])->toBe(['paywall' => true]);
    });
});

describe('Pivot Reattach Events', function () {
    it('fires only pivotReattaching and pivotReattached when reattaching', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark pivot for deletion
        $post->featured()->detach([$featured->getKey()]);

        $tracker = PivotEventTracker::make();

        $post->featured()->reattach([$featured->getKey()]);

        $tracker->assertOnly(['pivotReattaching', 'pivotReattached']);
    });

    it('receives correct payload in pivotReattaching event', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->detach([$featured->getKey()]);

        $eventPayload = null;

        Post::pivotReattaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->reattach([$featured->getKey()]);

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

        $tracker = PivotEventTracker::make();

        // Reattach without specifying IDs - should only get published ones
        $post->featured()->reattach();

        $tracker->assertOnly(['pivotReattaching', 'pivotReattached']);
        expect($eventPayload['pivotIds'])->toContain($featured->getKey());
        expect($eventPayload['pivotIds'])->not->toContain($newFeatured->getKey());
    });

    it('restores the pivot to visible state after reattach', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->detach([$featured->getKey()]);
        expect($post->featured()->get())->toBeEmpty();

        $post->featured()->reattach([$featured->getKey()]);
        expect($post->featured()->count())->toBe(1);
    });
});

describe('Pivot Discard Events', function () {
    it('fires only pivotDiscarding and pivotDiscarded when discarding', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $tracker = PivotEventTracker::make();

        $post->featured()->discard([$featured->getKey()]);

        $tracker->assertOnly(['pivotDiscarding', 'pivotDiscarded']);
    });

    it('receives correct payload in pivotDiscarding event', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $eventPayload = null;

        Post::pivotDiscarding(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->discard([$featured->getKey()]);

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

        $tracker = PivotEventTracker::make();

        // Discard without specifying IDs - should only get unpublished ones
        $post->featured()->discard();

        $tracker->assertOnly(['pivotDiscarding', 'pivotDiscarded']);
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

        expect($post->featured()->count())->toBe(0);
    });
});

describe('Pivot Publish Events', function () {
    it('fires only pivotAttaching and pivotAttached when publishing pivots', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $tracker = PivotEventTracker::make();

        $post->featured()->publish([$featured->getKey()]);

        $tracker->assertOnly(['pivotAttaching', 'pivotAttached']);
    });

    it('receives correct payload in pivotAttaching event during publish', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $eventPayload = null;

        Post::pivotAttaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->publish([$featured->getKey()]);

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

        $tracker = PivotEventTracker::make();

        // Publish without specifying IDs - should only get unpublished ones
        $post->featured()->publish();

        $tracker->assertOnly(['pivotAttaching', 'pivotAttached']);
        expect($eventPayload['pivotIds'])->toContain($newFeatured->getKey());
        expect($eventPayload['pivotIds'])->not->toContain($featured->getKey());
    });

    it('marks the pivot as published after publish', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $pivot = $post->featured()->withPivot(['has_been_published'])->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();

        $post->featured()->publish([$featured->getKey()]);

        $pivot = $post->featured()->withPivot(['has_been_published'])->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
    });
});

describe('Pivot Flush Events', function () {
    it('fires only pivotDetaching and pivotDetached when flushing', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Mark pivot for deletion
        $post->featured()->detach([$featured->getKey()]);

        $tracker = PivotEventTracker::make();

        $post->featured()->flush([$featured->getKey()]);

        $tracker->assertOnly(['pivotDetaching', 'pivotDetached']);
    });

    it('receives correct payload in pivotDetaching event during flush', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->detach([$featured->getKey()]);

        $eventPayload = null;

        Post::pivotDetaching(function ($model, $relation, $pivotIds) use (&$eventPayload) {
            $eventPayload = [
                'model' => $model,
                'pivotIds' => $pivotIds,
            ];
        });

        $post->featured()->flush([$featured->getKey()]);

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

        $tracker = PivotEventTracker::make();

        // Flush without specifying IDs - should only get ones marked for deletion
        $post->featured()->flush();

        $tracker->assertOnly(['pivotDetaching', 'pivotDetached']);
        expect($eventPayload['pivotIds'])->toContain($featured1->getKey());
        expect($eventPayload['pivotIds'])->not->toContain($featured2->getKey());
    });

    it('permanently removes the pivot after flush', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->detach([$featured->getKey()]);
        $post->featured()->flush([$featured->getKey()]);

        $count = \Illuminate\Support\Facades\DB::table('post_post')
            ->where('post_id', $post->getKey())
            ->where('featured_id', $featured->getKey())
            ->count();

        expect($count)->toBe(0);
    });
});

describe('Pivot Events with MorphToMany relations', function () {
    it('fires only pivotDraftAttaching and pivotDraftAttached on morphToMany when parent is draft', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $media = Media::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->media()->attach([$media->getKey()]);

        $tracker->assertOnly(['pivotDraftAttaching', 'pivotDraftAttached']);
    });

    it('fires only pivotDraftDetaching and pivotDraftDetached on morphToMany when parent is draft', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $media = Media::factory()->create();
        $post->media()->attach([$media->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $tracker = PivotEventTracker::make();

        $post->media()->detach([$media->getKey()]);

        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);
    });

    it('fires only pivotDraftSyncing and pivotDraftSynced on morphToMany when parent is draft', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $media = Media::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->media()->sync([$media->getKey()]);

        $tracker->assertOnly(['pivotDraftSyncing', 'pivotDraftSynced']);
    });

    it('fires only pivotAttaching and pivotAttached on morphToMany when parent is published', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $media = Media::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->media()->attach([$media->getKey()]);

        $tracker->assertOnly(['pivotAttaching', 'pivotAttached']);
    });
});

describe('Pivot Events with custom pivot classes', function () {
    it('fires only draft events with custom pivot class on BelongsToMany when parent is draft', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->customFeatured()->attach([$featured->getKey()]);

        $tracker->assertOnly(['pivotDraftAttaching', 'pivotDraftAttached']);
    });

    it('fires only draft detach events with custom pivot class when parent is draft', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->customFeatured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $tracker = PivotEventTracker::make();

        $post->customFeatured()->detach([$featured->getKey()]);

        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);
    });

    it('fires only draft events with custom pivot class on MorphToMany when parent is draft', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $media = Media::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->customMedia()->attach([$media->getKey()]);

        $tracker->assertOnly(['pivotDraftAttaching', 'pivotDraftAttached']);
    });
});

describe('Pivot Event Cancellation', function () {
    it('can cancel pivotDraftAttaching and prevent the attach - fires only pivotDraftAttaching', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        // Register tracker first so it sees the event before cancellation halts propagation
        $tracker = PivotEventTracker::make();

        Post::pivotDraftAttaching(function () {
            return false;
        });

        $result = $post->featured()->attach([$featured->getKey()]);

        expect($result)->toBeFalse();
        expect($post->featured()->count())->toBe(0);
        $tracker->assertOnly(['pivotDraftAttaching']);
    });

    it('can cancel pivotDraftDetaching and prevent the detach - fires only pivotDraftDetaching', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Register tracker first so it sees the event before cancellation halts propagation
        $tracker = PivotEventTracker::make();

        Post::pivotDraftDetaching(function () {
            return false;
        });

        $result = $post->featured()->detach([$featured->getKey()]);

        expect($result)->toBeFalse();
        expect($post->featured()->count())->toBe(1);
        $tracker->assertOnly(['pivotDraftDetaching']);
    });

    it('can cancel pivotDraftSyncing and prevent the sync - fires only pivotDraftSyncing', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        // Register tracker first so it sees the event before cancellation halts propagation
        $tracker = PivotEventTracker::make();

        Post::pivotDraftSyncing(function () {
            return false;
        });

        $result = $post->featured()->sync([$featured->getKey()]);

        expect($result)->toBeFalse();
        expect($post->featured()->count())->toBe(0);
        $tracker->assertOnly(['pivotDraftSyncing']);
    });
});

describe('Event order verification', function () {
    it('fires events in correct order: attaching before attached', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->featured()->attach([$featured->getKey()]);

        expect($tracker->firedEvents[0])->toBe('pivotDraftAttaching');
        expect($tracker->firedEvents[1])->toBe('pivotDraftAttached');
    });

    it('fires events in correct order: detaching before detached', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $tracker = PivotEventTracker::make();

        $post->featured()->detach([$featured->getKey()]);

        expect($tracker->firedEvents[0])->toBe('pivotDraftDetaching');
        expect($tracker->firedEvents[1])->toBe('pivotDraftDetached');
    });

    it('fires events in correct order: syncing before synced', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        $tracker = PivotEventTracker::make();

        $post->featured()->sync([$featured->getKey()]);

        expect($tracker->firedEvents[0])->toBe('pivotDraftSyncing');
        expect($tracker->firedEvents[1])->toBe('pivotDraftSynced');
    });

    it('fires events in correct order: reattaching before reattached', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->detach([$featured->getKey()]);

        $tracker = PivotEventTracker::make();

        $post->featured()->reattach([$featured->getKey()]);

        expect($tracker->firedEvents[0])->toBe('pivotReattaching');
        expect($tracker->firedEvents[1])->toBe('pivotReattached');
    });

    it('fires events in correct order: discarding before discarded', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();
        $post->featured()->attach([$featured->getKey()]);

        $tracker = PivotEventTracker::make();

        $post->featured()->discard([$featured->getKey()]);

        expect($tracker->firedEvents[0])->toBe('pivotDiscarding');
        expect($tracker->firedEvents[1])->toBe('pivotDiscarded');
    });
});

describe('Pivot event consistency tests', function () {
    it('treats never-published models the same as was-published-now-draft models', function () {
        // Consistency test: All unpublished models should behave the same way,
        // regardless of whether they were ever published before.
        //
        // This ensures a simpler mental model:
        // - Published = pivotAttaching/pivotAttached events
        // - Not published = pivotDraftAttaching/pivotDraftAttached events
        $neverPublished = Post::factory()->create(['status' => Status::DRAFT]);
        $wasPublished = Post::factory()->create(['status' => Status::PUBLISHED]);
        $wasPublished->status = Status::DRAFT;
        $wasPublished->save();

        $featured1 = Post::factory()->create();
        $featured2 = Post::factory()->create();

        // Attach to never-published model
        $neverPublished->featured()->attach([$featured1->getKey()]);
        $pivot1 = $neverPublished->featured()->withPivot(['has_been_published'])->first()->pivot;

        // Attach to was-published model
        $wasPublished->featured()->attach([$featured2->getKey()]);
        $pivot2 = $wasPublished->featured()->withPivot(['has_been_published'])->first()->pivot;

        // Both should have the same has_been_published value (false)
        expect((bool) $pivot1->has_been_published)->toBeFalse();
        expect((bool) $pivot2->has_been_published)->toBeFalse();
    });

    it('fires pivotAttaching and pivotAttached when publishing a never-published model with draft pivots', function () {
        // When a model is published for the first time, pivots attached while it was
        // a draft should fire pivotAttaching/pivotAttached events.
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        // Attach while draft - fires pivotDraftAttaching/pivotDraftAttached
        $post->featured()->attach([$featured->getKey()]);

        $tracker = PivotEventTracker::make();

        // Publish - should fire pivotAttaching/pivotAttached for the draft pivot
        $post->status = Status::PUBLISHED;
        $post->save();

        // The 'featured' relation should have fired pivotAttaching/pivotAttached
        expect($tracker->firedEvents)->toContain('pivotAttaching');
        expect($tracker->firedEvents)->toContain('pivotAttached');
    });
});
