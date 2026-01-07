<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Support\PivotEventTracker;

beforeEach(function () {
    Publisher::allowDraftContent();
});

describe('Repeated draftAttach/draftDetach cycles on draft-only pivot', function () {
    it('correctly handles attach -> detach -> attach -> detach cycle on draft parent', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        // First attach
        $tracker = PivotEventTracker::make();
        $post->featured()->attach([$featured->getKey()]);
        $tracker->assertOnly(['pivotDraftAttaching', 'pivotDraftAttached']);

        // First detach (marks for deletion)
        $tracker = PivotEventTracker::make();
        $post->featured()->detach([$featured->getKey()]);
        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);

        // Second attach (reattaches the marked pivot)
        $tracker = PivotEventTracker::make();
        $post->featured()->attach([$featured->getKey()]);
        $tracker->assertOnly(['pivotReattaching', 'pivotReattached']);

        // Second detach (marks for deletion again)
        $tracker = PivotEventTracker::make();
        $post->featured()->detach([$featured->getKey()]);
        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);

        // Verify pivot is marked for deletion
        $pivot = \Illuminate\Support\Facades\DB::table('post_post')
            ->where('post_id', $post->getKey())
            ->where('featured_id', $featured->getKey())
            ->first();
        expect((bool) $pivot->should_delete)->toBeTrue();
        expect((bool) $pivot->has_been_published)->toBeFalse();
    });

    it('maintains correct state through multiple cycles', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        // Cycle 1
        $post->featured()->attach([$featured->getKey()]);
        $post->featured()->detach([$featured->getKey()]);

        // Cycle 2
        $post->featured()->attach([$featured->getKey()]);
        $post->featured()->detach([$featured->getKey()]);

        // Cycle 3
        $post->featured()->attach([$featured->getKey()]);

        // Final state: pivot should exist and not be marked for deletion
        $pivot = \Illuminate\Support\Facades\DB::table('post_post')
            ->where('post_id', $post->getKey())
            ->where('featured_id', $featured->getKey())
            ->first();

        expect($pivot)->not->toBeNull();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });
});

describe('Repeated draftAttach/draftDetach cycles on published pivot', function () {
    it('correctly handles detach -> attach -> detach -> attach cycle on published pivot', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();

        // Attach while published (becomes has_been_published=true)
        $post->featured()->attach([$featured->getKey()]);

        // Unpublish parent
        $post->status = Status::DRAFT;
        $post->save();

        // First detach (marks for deletion)
        $tracker = PivotEventTracker::make();
        $post->featured()->detach([$featured->getKey()]);
        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);

        // First reattach
        $tracker = PivotEventTracker::make();
        $post->featured()->attach([$featured->getKey()]);
        $tracker->assertOnly(['pivotReattaching', 'pivotReattached']);

        // Second detach
        $tracker = PivotEventTracker::make();
        $post->featured()->detach([$featured->getKey()]);
        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);

        // Second reattach
        $tracker = PivotEventTracker::make();
        $post->featured()->attach([$featured->getKey()]);
        $tracker->assertOnly(['pivotReattaching', 'pivotReattached']);

        // Verify pivot state
        $pivot = \Illuminate\Support\Facades\DB::table('post_post')
            ->where('post_id', $post->getKey())
            ->where('featured_id', $featured->getKey())
            ->first();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect((bool) $pivot->has_been_published)->toBeTrue();
    });
});

describe('No-op operations fire no events', function () {
    it('does not fire events when draftDetach is called on already-marked pivot', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()]);
        $post->featured()->detach([$featured->getKey()]);

        // Second detach on same pivot - should be no-op
        $tracker = PivotEventTracker::make();
        $post->featured()->detach([$featured->getKey()]);
        expect($tracker->firedEvents)->toBeEmpty();
    });

    it('does not fire events when reattach is called on non-marked pivot', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()]);

        $post->status = Status::DRAFT;
        $post->save();

        // Reattach on pivot that is not marked for deletion - should be no-op
        $tracker = PivotEventTracker::make();
        $post->featured()->reattach([$featured->getKey()]);
        expect($tracker->firedEvents)->toBeEmpty();
    });

    it('fires no events when all IDs being attached already have pivots not marked for deletion', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()]);

        // Detach then reattach to get a pivot marked should_delete=false
        $post->featured()->detach([$featured->getKey()]);
        $post->featured()->attach([$featured->getKey()]); // Reattach

        // Now try to attach again - should be no-op since pivot exists and is not marked for deletion
        $tracker = PivotEventTracker::make();
        $post->featured()->attach([$featured->getKey()]);

        // The draftAttach filters out IDs that are already attached (not marked for deletion)
        // and only fires events for IDs that are either new or need reattaching
        // Since this ID doesn't fit either category, no events should fire
        expect($tracker->firedEvents)->not->toContain('pivotDraftAttaching');
        expect($tracker->firedEvents)->not->toContain('pivotReattaching');
    });
});

describe('Parent state transitions with pivot cycles', function () {
    it('correctly handles draft attach -> publish -> draft detach -> publish cycle', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        // Attach while draft
        $post->featured()->attach([$featured->getKey()]);

        // Publish - pivot becomes published
        $tracker = PivotEventTracker::make();
        $post->status = Status::PUBLISHED;
        $post->save();
        expect($tracker->firedEvents)->toContain('pivotAttaching');
        expect($tracker->firedEvents)->toContain('pivotAttached');

        // Unpublish
        $post->status = Status::DRAFT;
        $post->save();

        // Detach while draft
        $tracker = PivotEventTracker::make();
        $post->featured()->detach([$featured->getKey()]);
        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);

        // Publish again - pivot should be detached
        $tracker = PivotEventTracker::make();
        $post->status = Status::PUBLISHED;
        $post->save();
        expect($tracker->firedEvents)->toContain('pivotDetaching');
        expect($tracker->firedEvents)->toContain('pivotDetached');

        // Verify pivot is gone
        $pivot = \Illuminate\Support\Facades\DB::table('post_post')
            ->where('post_id', $post->getKey())
            ->where('featured_id', $featured->getKey())
            ->first();
        expect($pivot)->toBeNull();
    });

    it('correctly discards draft-only pivot on publish', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        // Attach while draft
        $post->featured()->attach([$featured->getKey()]);

        // Detach while draft (marks for deletion)
        $post->featured()->detach([$featured->getKey()]);

        // Publish - draft-only pivot should be discarded
        $tracker = PivotEventTracker::make();
        $post->status = Status::PUBLISHED;
        $post->save();

        // Should fire discard events (not detach) for draft-only pivot
        expect($tracker->firedEvents)->toContain('pivotDiscarding');
        expect($tracker->firedEvents)->toContain('pivotDiscarded');
        expect($tracker->firedEvents)->not->toContain('pivotDetaching');
        expect($tracker->firedEvents)->not->toContain('pivotDetached');
    });

    it('correctly handles revert with should_delete on draft-only pivot', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);

        // Unpublish
        $post->status = Status::DRAFT;
        $post->save();

        $featured = Post::factory()->create();

        // Attach while draft (draft-only pivot)
        $post->featured()->attach([$featured->getKey()]);

        // Detach while draft (marks for deletion)
        $post->featured()->detach([$featured->getKey()]);

        // Revert - draft-only pivot should be discarded
        $tracker = PivotEventTracker::make();
        $post->revert();

        // The revert process calls reattach first (which would set should_delete=false)
        // then discard (which deletes has_been_published=false pivots)
        expect($tracker->firedEvents)->toContain('pivotReattaching');
        expect($tracker->firedEvents)->toContain('pivotReattached');
        expect($tracker->firedEvents)->toContain('pivotDiscarding');
        expect($tracker->firedEvents)->toContain('pivotDiscarded');

        // Pivot should be gone
        $pivot = \Illuminate\Support\Facades\DB::table('post_post')
            ->where('post_id', $post->getKey())
            ->where('featured_id', $featured->getKey())
            ->first();
        expect($pivot)->toBeNull();
    });
});

describe('Mixed pivot operations', function () {
    it('correctly handles mixed attach with some needing reattach and some new', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $existingFeatured = Post::factory()->create();
        $newFeatured = Post::factory()->create();

        // Attach one while published
        $post->featured()->attach([$existingFeatured->getKey()]);

        // Unpublish and detach
        $post->status = Status::DRAFT;
        $post->save();
        $post->featured()->detach([$existingFeatured->getKey()]);

        $tracker = PivotEventTracker::make();

        // Attach both: one reattach, one new
        $post->featured()->attach([
            $existingFeatured->getKey(),
            $newFeatured->getKey(),
        ]);

        // Should fire reattach events for existing
        expect($tracker->firedEvents)->toContain('pivotReattaching');
        expect($tracker->firedEvents)->toContain('pivotReattached');

        // Should fire draft attach events for new
        expect($tracker->firedEvents)->toContain('pivotDraftAttaching');
        expect($tracker->firedEvents)->toContain('pivotDraftAttached');
    });

    it('correctly handles flush with mixed pivot types during publish', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        $publishedFeatured = Post::factory()->create();

        // Attach while published
        $post->featured()->attach([$publishedFeatured->getKey()]);

        // Unpublish
        $post->status = Status::DRAFT;
        $post->save();

        // Attach a new one (draft-only)
        $draftFeatured = Post::factory()->create();
        $post->featured()->attach([$draftFeatured->getKey()]);

        // Detach both
        $post->featured()->detach([
            $publishedFeatured->getKey(),
            $draftFeatured->getKey(),
        ]);

        // Publish - should fire different events for each pivot type
        $tracker = PivotEventTracker::make();
        $post->status = Status::PUBLISHED;
        $post->save();

        // Draft-only pivot fires discard events
        expect($tracker->firedEvents)->toContain('pivotDiscarding');
        expect($tracker->firedEvents)->toContain('pivotDiscarded');

        // Published pivot fires detach events
        expect($tracker->firedEvents)->toContain('pivotDetaching');
        expect($tracker->firedEvents)->toContain('pivotDetached');
    });
});

describe('Custom pivot class cycles', function () {
    it('correctly handles attach/detach cycles with custom pivot class', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        // First attach
        $tracker = PivotEventTracker::make();
        $post->customFeatured()->attach([$featured->getKey()]);
        $tracker->assertOnly(['pivotDraftAttaching', 'pivotDraftAttached']);

        // First detach
        $tracker = PivotEventTracker::make();
        $post->customFeatured()->detach([$featured->getKey()]);
        $tracker->assertOnly(['pivotDraftDetaching', 'pivotDraftDetached']);

        // Reattach
        $tracker = PivotEventTracker::make();
        $post->customFeatured()->attach([$featured->getKey()]);
        $tracker->assertOnly(['pivotReattaching', 'pivotReattached']);
    });

    it('does not fire events for no-op detach with custom pivot class', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()]);
        $post->customFeatured()->detach([$featured->getKey()]);

        // Second detach - should be no-op
        $tracker = PivotEventTracker::make();
        $post->customFeatured()->detach([$featured->getKey()]);
        expect($tracker->firedEvents)->toBeEmpty();
    });
});

describe('Edge cases', function () {
    it('handles detach with no matching pivots gracefully', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        // Detach pivot that was never attached
        $tracker = PivotEventTracker::make();
        $post->featured()->detach([$featured->getKey()]);
        expect($tracker->firedEvents)->toBeEmpty();
    });

    it('handles reattach with no matching pivots gracefully', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);
        $featured = Post::factory()->create();

        // Reattach pivot that was never attached
        $tracker = PivotEventTracker::make();
        $post->featured()->reattach([$featured->getKey()]);
        expect($tracker->firedEvents)->toBeEmpty();
    });

    it('handles empty ID arrays gracefully', function () {
        $post = Post::factory()->create(['status' => Status::DRAFT]);

        // Empty arrays should not create any pivots
        $post->featured()->attach([]);
        $post->featured()->detach([]);
        $post->featured()->reattach([]);

        expect($post->featured()->count())->toBe(0);
    });
});
