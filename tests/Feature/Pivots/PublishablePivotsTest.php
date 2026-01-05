<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;

describe('Publishable BelongsToMany relationships can be managed via publishing status', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('attaches records correctly on publishable pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()]);

        $pivot = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('attaches records correctly on publishable pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()]);

        $pivot = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        // Pivots attached to unpublished parents are marked as draft (has_been_published=false)
        // They will be published when the parent is published via publishAllPivots()
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('detaches records correctly on publishable pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()]);
        $post->featured()->detach([$featured->getKey()]);

        expect($post->featured()->get())->toBeEmpty();
    });

    it('detaches records correctly on publishable pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()]);
        $post->featured()->detach([$featured->getKey()]);

        expect($post->featured()->get())->toBeEmpty();
    });

    it('toggles records correctly on publishable pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->featured()->attach([$attached->getKey()]);
        $post->featured()->toggle([$attached->getKey(), $notAttached->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->not->toContain($attached->id);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('toggles records correctly on publishable pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->featured()->attach([$attached->getKey()]);
        $post->featured()->toggle([$attached->getKey(), $notAttached->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->where('id', $notAttached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('syncs records correctly on publishable pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->featured()->attach([$attached->getKey()]);
        $post->featured()->sync([$notAttached->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->not->toContain($attached->id);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('syncs records correctly on publishable pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->featured()->attach([$attached->getKey()]);
        $post->featured()->sync([$notAttached->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->where('id', $notAttached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('syncs without detaching records correctly on publishable pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->featured()->attach([$attached->getKey()]);
        $post->featured()->syncWithoutDetaching([$notAttached->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(2);
        expect($ids)->toContain($attached->id);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->where('id', $attached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $pivot = $posts->where('id', $notAttached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('syncs without detaching records correctly on publishable pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->featured()->attach([$attached->getKey()]);
        $post->featured()->syncWithoutDetaching([$notAttached->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(2);
        expect($ids)->toContain($attached->id);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->where('id', $attached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $pivot = $posts->where('id', $notAttached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('syncs with pivot values records correctly on publishable pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->featured()->attach([$attached->getKey()]);
        $post->featured()->syncWithPivotValues([$notAttached->getKey()], [
            'paywall' => true,
        ]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'paywall',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->not->toContain($attached->id);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect((bool) $pivot->paywall)->toBeTrue();
    });

    it('syncs with pivot values records correctly on publishable pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->featured()->attach([$attached->getKey()]);
        $post->featured()->syncWithPivotValues([$notAttached->getKey()], [
            'paywall' => true,
        ]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'paywall',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->where('id', $notAttached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect((bool) $pivot->paywall)->toBeTrue();
    });

    it('updates existing pivot records correctly on publishable pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'paywall',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($featured->id);

        $pivot = $posts->where('id', $featured->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect((bool) $pivot->paywall)->toBeFalse();

        $originalPivotId = $pivot->id;

        $post->featured()->updateExistingPivot($featured->getKey(), [
            'paywall' => true,
        ]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'paywall',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($featured->id);

        $pivot = $posts->where('id', $featured->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect((bool) $pivot->paywall)->toBeTrue();
        expect($pivot->id)->toBe($originalPivotId);
    });

    it('updates existing pivot records correctly on publishable pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'paywall',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($featured->id);

        $pivot = $posts->where('id', $featured->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect((bool) $pivot->paywall)->toBeFalse();

        $originalPivotId = $pivot->id;

        $post->featured()->updateExistingPivot($featured->getKey(), [
            'paywall' => true,
        ]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'paywall',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($featured->id);

        $pivot = $posts->where('id', $featured->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect((bool) $pivot->paywall)->toBeTrue();
        expect($pivot->id)->toBe($originalPivotId);
    });

    it('updates the publishable pivot relations correctly when the parent is being published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attachedPublishedAndRemains = Post::factory()->create();
        $attachedPublishedAndWillBeDetached = Post::factory()->create();

        $post->featured()->attach([
            $attachedPublishedAndRemains->getKey(),
            $attachedPublishedAndWillBeDetached->getKey(),
        ]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(2);
        expect($ids)->toContain($attachedPublishedAndRemains->id);
        expect($ids)->toContain($attachedPublishedAndWillBeDetached->id);

        $pivot = $posts->where('id', $attachedPublishedAndRemains->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $pivot = $posts->where('id', $attachedPublishedAndWillBeDetached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $post->status = Status::DRAFT;
        $post->save();

        $attachedDraftAndRemains = Post::factory()->create();
        $attachedDraftAndWillBeDetached = Post::factory()->create();

        $post->featured()->attach([
            $attachedDraftAndRemains->getKey(),
            $attachedDraftAndWillBeDetached->getKey(),
        ]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(4);
        expect($ids)->toContain($attachedPublishedAndRemains->id);
        expect($ids)->toContain($attachedPublishedAndWillBeDetached->id);
        expect($ids)->toContain($attachedDraftAndRemains->id);
        expect($ids)->toContain($attachedDraftAndWillBeDetached->id);

        $pivot = $posts->where('id', $attachedPublishedAndRemains->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $pivot = $posts->where('id', $attachedPublishedAndWillBeDetached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $pivot = $posts->where('id', $attachedDraftAndRemains->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $pivot = $posts->where('id', $attachedDraftAndWillBeDetached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $post->featured()->sync([$attachedPublishedAndRemains->getKey(), $attachedDraftAndRemains->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(2);
        expect($ids)->toContain($attachedPublishedAndRemains->id);
        expect($ids)->toContain($attachedDraftAndRemains->id);

        $pivot = $posts->where('id', $attachedPublishedAndRemains->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $pivot = $posts->where('id', $attachedDraftAndRemains->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $post->status = Status::PUBLISHED;
        $post->save();

        $posts = $post->featured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(2);
        expect($ids)->toContain($attachedPublishedAndRemains->id);
        expect($ids)->not->toContain($attachedPublishedAndWillBeDetached->id);
        expect($ids)->toContain($attachedDraftAndRemains->id);
        expect($ids)->not->toContain($attachedDraftAndWillBeDetached->id);

        $pivot = $posts->where('id', $attachedPublishedAndRemains->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();

        $pivot = $posts->where('id', $attachedDraftAndRemains->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });
});

describe('Attach/detach/re-attach cycles on publishable pivots', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('reattaches a previously published pivot without creating duplicates', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->featured()->attach([$featured->getKey()]);

        $pivot = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeTrue();
        $originalPivotId = $pivot->id;

        // Unpublish parent
        $post->status = Status::DRAFT;
        $post->save();

        // Detach (marks for deletion)
        $post->featured()->detach([$featured->getKey()]);

        // Verify pivot is marked for deletion
        expect($post->featured()->get())->toBeEmpty();

        // Re-attach the same item
        $post->featured()->attach([$featured->getKey()]);

        // Verify: same pivot record (no duplicate), should_delete cleared
        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        $pivot = $posts->first()->pivot;
        expect($pivot->id)->toBe($originalPivotId);
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('reattaches with attributes applied to draft column', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published with initial attributes
        $post->featured()->attach([$featured->getKey() => ['order' => 1]]);

        $pivot = $post->featured()->withPivot([
            'id',
            'order',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((int) $pivot->order)->toBe(1);
        $originalPivotId = $pivot->id;

        // Unpublish and detach
        $post->status = Status::DRAFT;
        $post->save();
        $post->featured()->detach([$featured->getKey()]);

        // Re-attach with new attributes
        $post->featured()->attach([$featured->getKey() => ['order' => 5]]);

        // Verify: same pivot, new attributes in draft
        $posts = $post->featured()->withPivot([
            'id',
            'order',
            'draft',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        $pivot = $posts->first()->pivot;
        expect($pivot->id)->toBe($originalPivotId);
        expect((int) $pivot->order)->toBe(5); // Draft value merged
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('handles mixed new and reattach IDs correctly', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $existingFeatured = Post::factory()->create();
        $newFeatured = Post::factory()->create();

        // Attach one while published
        $post->featured()->attach([$existingFeatured->getKey()]);

        // Unpublish and detach
        $post->status = Status::DRAFT;
        $post->save();
        $post->featured()->detach([$existingFeatured->getKey()]);

        // Attach both: one reattach, one new
        $post->featured()->attach([
            $existingFeatured->getKey(),
            $newFeatured->getKey(),
        ]);

        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(2);

        // Existing should have has_been_published = true (reattached)
        $existingPivot = $posts->where('id', $existingFeatured->id)->first()->pivot;
        expect((bool) $existingPivot->has_been_published)->toBeTrue();
        expect((bool) $existingPivot->should_delete)->toBeFalse();

        // New should have has_been_published = false (fresh attach)
        $newPivot = $posts->where('id', $newFeatured->id)->first()->pivot;
        expect((bool) $newPivot->has_been_published)->toBeFalse();
        expect((bool) $newPivot->should_delete)->toBeFalse();
    });

    it('sync handles reattachment of previously detached items', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured1 = Post::factory()->create();
        $featured2 = Post::factory()->create();

        // Attach both while published
        $post->featured()->attach([
            $featured1->getKey(),
            $featured2->getKey(),
        ]);

        // Get original pivot IDs
        $pivots = $post->featured()->withPivot(['id'])->get();
        $pivot1Id = $pivots->where('id', $featured1->id)->first()->pivot->id;
        $pivot2Id = $pivots->where('id', $featured2->id)->first()->pivot->id;

        // Unpublish
        $post->status = Status::DRAFT;
        $post->save();

        // Sync to only featured2 (detaches featured1)
        $post->featured()->sync([$featured2->getKey()]);

        expect($post->featured()->get())->toHaveCount(1);

        // Sync back to both (should reattach featured1)
        $post->featured()->sync([
            $featured1->getKey(),
            $featured2->getKey(),
        ]);

        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(2);

        // featured1 should be reattached (same pivot ID)
        $pivot1 = $posts->where('id', $featured1->id)->first()->pivot;
        expect($pivot1->id)->toBe($pivot1Id);
        expect((bool) $pivot1->has_been_published)->toBeTrue();
        expect((bool) $pivot1->should_delete)->toBeFalse();
    });

    it('toggle handles reattachment correctly', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->featured()->attach([$featured->getKey()]);

        $originalPivotId = $post->featured()->withPivot(['id'])->first()->pivot->id;

        // Unpublish
        $post->status = Status::DRAFT;
        $post->save();

        // Toggle off (detaches)
        $post->featured()->toggle([$featured->getKey()]);
        expect($post->featured()->get())->toBeEmpty();

        // Toggle on (should reattach)
        $post->featured()->toggle([$featured->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        $pivot = $posts->first()->pivot;
        expect($pivot->id)->toBe($originalPivotId);
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('does not create duplicates when repeatedly attaching and detaching', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->featured()->attach([$featured->getKey()]);
        $originalPivotId = $post->featured()->withPivot(['id'])->first()->pivot->id;

        // Unpublish
        $post->status = Status::DRAFT;
        $post->save();

        // Repeatedly attach/detach
        for ($i = 0; $i < 5; $i++) {
            $post->featured()->detach([$featured->getKey()]);
            $post->featured()->attach([$featured->getKey()]);
        }

        // Verify only one pivot record exists
        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        expect($posts->first()->pivot->id)->toBe($originalPivotId);
    });

    it('actually deletes draft-only pivots on detach and creates fresh on re-attach', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        // Attach while draft
        $post->featured()->attach([$featured->getKey()]);

        $pivot = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeFalse();
        $originalPivotId = $pivot->id;

        // Detach (should actually delete draft-only pivot)
        $post->featured()->detach([$featured->getKey()]);

        // Verify pivot is actually gone from database (not just marked)
        $rawCount = \DB::table('post_post')
            ->where('post_id', $post->id)
            ->where('featured_id', $featured->id)
            ->count();

        expect($rawCount)->toBe(0);

        // Re-attach creates a fresh pivot
        $post->featured()->attach([$featured->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        $newPivot = $posts->first()->pivot;
        expect($newPivot->id)->not->toBe($originalPivotId); // New record
        expect((bool) $newPivot->has_been_published)->toBeFalse();
        expect((bool) $newPivot->should_delete)->toBeFalse();
    });

    it('creates fresh pivots when repeatedly attaching and detaching draft-only pivots', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        $pivotIds = [];

        // Repeatedly attach/detach on a draft parent
        for ($i = 0; $i < 3; $i++) {
            $post->featured()->attach([$featured->getKey()]);
            $pivotIds[] = $post->featured()->withPivot(['id'])->first()->pivot->id;
            $post->featured()->detach([$featured->getKey()]);
        }

        // All pivot IDs should be different (each attach creates new record)
        expect(count(array_unique($pivotIds)))->toBe(3);

        // No pivot records should remain
        $rawCount = \DB::table('post_post')
            ->where('post_id', $post->id)
            ->where('featured_id', $featured->id)
            ->count();

        expect($rawCount)->toBe(0);
    });

    it('reattach method clears the should_delete flag', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->featured()->attach([$featured->getKey()]);

        $pivot = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
        $originalPivotId = $pivot->id;

        // Unpublish parent
        $post->status = Status::DRAFT;
        $post->save();

        // Detach (marks for deletion, not actually deleted)
        $post->featured()->detach([$featured->getKey()]);

        // Verify pivot is marked for deletion in raw database
        $rawPivot = \DB::table('post_post')
            ->where('post_id', $post->id)
            ->where('featured_id', $featured->id)
            ->first();

        expect($rawPivot)->not->toBeNull();
        expect((bool) $rawPivot->should_delete)->toBeTrue();

        // Call reattach explicitly
        $reattachedCount = $post->featured()->reattach([$featured->getKey()]);

        expect($reattachedCount)->toBe(1);

        // Verify should_delete flag is cleared
        $rawPivot = \DB::table('post_post')
            ->where('post_id', $post->id)
            ->where('featured_id', $featured->id)
            ->first();

        expect($rawPivot)->not->toBeNull();
        expect((bool) $rawPivot->should_delete)->toBeFalse();
        expect($rawPivot->id)->toBe($originalPivotId);

        // Verify pivot is accessible through the relationship again
        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        $pivot = $posts->first()->pivot;
        expect($pivot->id)->toBe($originalPivotId);
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('reattached pivot is not deleted when parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        // Attach while published
        $post->featured()->attach([$featured->getKey()]);

        $pivot = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        $originalPivotId = $pivot->id;

        // Unpublish parent
        $post->status = Status::DRAFT;
        $post->save();

        // Detach (marks for deletion)
        $post->featured()->detach([$featured->getKey()]);

        // Verify pivot is marked for deletion but has_been_published is still true
        $rawPivot = \DB::table('post_post')
            ->where('post_id', $post->id)
            ->where('featured_id', $featured->id)
            ->first();

        expect((bool) $rawPivot->should_delete)->toBeTrue();
        expect((bool) $rawPivot->has_been_published)->toBeTrue();

        // Call reattach to bring it back and verify it found the pivot
        $reattachedCount = $post->featured()->reattach([$featured->getKey()]);

        expect($reattachedCount)->toBe(1);

        // Verify should_delete is now false
        $rawPivot = \DB::table('post_post')
            ->where('post_id', $post->id)
            ->where('featured_id', $featured->id)
            ->first();

        expect((bool) $rawPivot->should_delete)->toBeFalse();

        // Publish the parent - the pivot should NOT be deleted
        $post->refresh();
        $post->status = Status::PUBLISHED;
        $post->save();

        // Verify pivot still exists after publishing
        $rawPivot = \DB::table('post_post')
            ->where('post_id', $post->id)
            ->where('featured_id', $featured->id)
            ->first();

        expect($rawPivot)->not->toBeNull();
        expect($rawPivot->id)->toBe($originalPivotId);

        // Verify pivot is accessible in both draft and published modes
        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        $pivot = $posts->first()->pivot;
        expect($pivot->id)->toBe($originalPivotId);
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();

        // Also verify it's visible when restricting to published content
        Publisher::restrictDraftContent();

        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        expect($posts->first()->pivot->id)->toBe($originalPivotId);
    });
});
