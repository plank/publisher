<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;

describe('Publishable BelongsToMany relationships can be managed via publishing status', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('attaches records correctly on publishable custom pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()]);

        $pivot = $post->customFeatured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('attaches records correctly on publishable custom pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()]);

        $pivot = $post->customFeatured()->withPivot([
            'id',
            'post_id',
            'featured_id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('detaches records correctly on publishable custom pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()]);
        $post->customFeatured()->detach([$featured->getKey()]);

        expect($post->customFeatured()->get())->toBeEmpty();
    });

    it('detaches records correctly on publishable custom pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()]);
        $post->customFeatured()->detach([$featured->getKey()]);

        expect($post->customFeatured()->get())->toBeEmpty();
    });

    it('toggles records correctly on publishable custom pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->customFeatured()->attach([$attached->getKey()]);
        $post->customFeatured()->toggle([$attached->getKey(), $notAttached->getKey()]);

        $posts = $post->customFeatured()->withPivot([
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

    it('toggles records correctly on publishable custom pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->customFeatured()->attach([$attached->getKey()]);
        $post->customFeatured()->toggle([$attached->getKey(), $notAttached->getKey()]);

        $posts = $post->customFeatured()->withPivot([
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

    it('syncs records correctly on publishable custom pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->customFeatured()->attach([$attached->getKey()]);
        $post->customFeatured()->sync([$notAttached->getKey()]);

        $posts = $post->customFeatured()->withPivot([
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

    it('syncs records correctly on publishable custom pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->customFeatured()->attach([$attached->getKey()]);
        $post->customFeatured()->sync([$notAttached->getKey()]);

        $posts = $post->customFeatured()->withPivot([
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

    it('syncs without detaching records correctly on publishable custom pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->customFeatured()->attach([$attached->getKey()]);
        $post->customFeatured()->syncWithoutDetaching([$notAttached->getKey()]);

        $posts = $post->customFeatured()->withPivot([
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

    it('syncs without detaching records correctly on publishable custom pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->customFeatured()->attach([$attached->getKey()]);
        $post->customFeatured()->syncWithoutDetaching([$notAttached->getKey()]);

        $posts = $post->customFeatured()->withPivot([
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

    it('syncs with pivot values records correctly on publishable custom pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->customFeatured()->attach([$attached->getKey()]);
        $post->customFeatured()->syncWithPivotValues([$notAttached->getKey()], [
            'paywall' => true,
        ]);

        $posts = $post->customFeatured()->withPivot([
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

    it('syncs with pivot values records correctly on publishable custom pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Post::factory()->create();
        $notAttached = Post::factory()->create();

        $post->customFeatured()->attach([$attached->getKey()]);
        $post->customFeatured()->syncWithPivotValues([$notAttached->getKey()], [
            'paywall' => true,
        ]);

        $posts = $post->customFeatured()->withPivot([
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

    it('updates existing pivot records correctly on publishable custom pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()]);

        $posts = $post->customFeatured()->withPivot([
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

        $post->customFeatured()->updateExistingPivot($featured->getKey(), [
            'paywall' => true,
        ]);

        $posts = $post->customFeatured()->withPivot([
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

    it('updates existing pivot records correctly on publishable custom pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()]);

        $posts = $post->customFeatured()->withPivot([
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

        $post->customFeatured()->updateExistingPivot($featured->getKey(), [
            'paywall' => true,
        ]);

        $posts = $post->customFeatured()->withPivot([
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

    it('updates the publishable custom pivot relations correctly when the parent is being published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attachedPublishedAndRemains = Post::factory()->create();
        $attachedPublishedAndWillBeDetached = Post::factory()->create();

        $post->customFeatured()->attach([
            $attachedPublishedAndRemains->getKey(),
            $attachedPublishedAndWillBeDetached->getKey(),
        ]);

        $posts = $post->customFeatured()->withPivot([
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

        $post->customFeatured()->attach([
            $attachedDraftAndRemains->getKey(),
            $attachedDraftAndWillBeDetached->getKey(),
        ]);

        $posts = $post->customFeatured()->withPivot([
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

        $post->customFeatured()->sync([$attachedPublishedAndRemains->getKey(), $attachedDraftAndRemains->getKey()]);

        $posts = $post->customFeatured()->withPivot([
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

        $posts = $post->customFeatured()->withPivot([
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

describe('Attach/detach/re-attach cycles on publishable custom pivots', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('reattaches a previously published custom pivot without creating duplicates', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()]);

        $pivot = $post->customFeatured()->withPivot([
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
        $post->customFeatured()->detach([$featured->getKey()]);

        // Verify pivot is marked for deletion
        expect($post->customFeatured()->get())->toBeEmpty();

        // Re-attach the same item
        $post->customFeatured()->attach([$featured->getKey()]);

        // Verify: same pivot record (no duplicate), should_delete cleared
        $posts = $post->customFeatured()->withPivot([
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

    it('reattaches custom pivot with attributes applied to draft column', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published with initial attributes
        $post->customFeatured()->attach([$featured->getKey() => ['order' => 1]]);

        $pivot = $post->customFeatured()->withPivot([
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
        $post->customFeatured()->detach([$featured->getKey()]);

        // Re-attach with new attributes
        $post->customFeatured()->attach([$featured->getKey() => ['order' => 5]]);

        // Verify: same pivot, new attributes in draft
        $posts = $post->customFeatured()->withPivot([
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

    it('does not create duplicates when repeatedly attaching and detaching custom pivots', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()]);
        $originalPivotId = $post->customFeatured()->withPivot(['id'])->first()->pivot->id;

        // Unpublish
        $post->status = Status::DRAFT;
        $post->save();

        // Repeatedly attach/detach
        for ($i = 0; $i < 5; $i++) {
            $post->customFeatured()->detach([$featured->getKey()]);
            $post->customFeatured()->attach([$featured->getKey()]);
        }

        // Verify only one pivot record exists
        $posts = $post->customFeatured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        expect($posts->first()->pivot->id)->toBe($originalPivotId);
    });
});
