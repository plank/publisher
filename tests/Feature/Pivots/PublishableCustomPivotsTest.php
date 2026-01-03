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
