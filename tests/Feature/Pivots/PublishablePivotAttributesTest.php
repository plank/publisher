<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;

describe('Publishable pivot attributes can be stored in draft state', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('stores pivot attribute changes in draft column when parent is draft and has been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);

        // Now draft the parent
        $post->status = Status::DRAFT;
        $post->save();

        // Update the pivot
        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 5]);

        // Check that the draft column has the changes
        $pivot = $post->customFeatured()->withPivot([
            'id',
            'order',
            'draft',
            'has_been_published',
        ])->first()->pivot;

        expect((int) $pivot->order)->toBe(1); // Real column should still be 1
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(5); // Draft should have the new value
    });

    it('updates real columns directly when parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);
        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 5]);

        $pivot = $post->customFeatured()->withPivot([
            'id',
            'order',
            'draft',
            'has_been_published',
        ])->first()->pivot;

        expect((int) $pivot->order)->toBe(5); // Real column should be updated
        expect($pivot->draft)->toBeNull(); // No draft
    });

    it('updates real columns directly when parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);
        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 5]);

        $pivot = $post->customFeatured()->withPivot([
            'id',
            'order',
            'draft',
            'has_been_published',
        ])->first()->pivot;

        expect((int) $pivot->order)->toBe(5); // Real column should be updated
        expect($pivot->draft)->toBeNull(); // No draft
    });

    it('publishes draft pivot attributes when parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);

        // Draft and update
        $post->status = Status::DRAFT;
        $post->save();

        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 10]);

        // Publish
        $post->status = Status::PUBLISHED;
        $post->save();

        $pivot = $post->customFeatured()->withPivot([
            'id',
            'order',
            'draft',
            'has_been_published',
        ])->first()->pivot;

        expect((int) $pivot->order)->toBe(10); // Real column now has the draft value
        expect($pivot->draft)->toBeNull(); // Draft is cleared
    });

    it('reverts draft pivot attributes when parent is reverted', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);

        // Draft and update
        $post->status = Status::DRAFT;
        $post->save();

        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 10]);

        // Revert
        $post->revert();

        $pivot = $post->customFeatured()->withPivot([
            'id',
            'order',
            'draft',
            'has_been_published',
        ])->first()->pivot;

        expect((int) $pivot->order)->toBe(1); // Real column still has original value
        expect($pivot->draft)->toBeNull(); // Draft is cleared
    });

    it('loads draft pivot values when accessing pivot on draft parent with draft content allowed', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);

        // Draft and update
        $post->status = Status::DRAFT;
        $post->save();

        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 10]);

        // Reload the post and check the pivot value
        $post = Post::find($post->id);

        Publisher::allowDraftContent();

        $pivot = $post->customFeatured()->withPivot(['order', 'draft'])->first()->pivot;

        // The pivot model should load the draft value
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(10);
    });

    it('accumulates multiple draft updates', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1, 'paywall' => false]);

        // Draft and update multiple times
        $post->status = Status::DRAFT;
        $post->save();

        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 5]);
        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['paywall' => true]);

        $pivot = $post->customFeatured()->withPivot([
            'id',
            'order',
            'paywall',
            'draft',
        ])->first()->pivot;

        // Real columns should be unchanged
        expect((int) $pivot->order)->toBe(1);
        expect((bool) $pivot->paywall)->toBeFalse();

        // Draft should have both updates
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(5);
        expect($pivot->draft['paywall'])->toBeTrue();
    });

    it('fires pivotDraftUpdating and pivotDraftUpdated events', function () {
        $updatingFired = false;
        $updatedFired = false;

        Post::pivotDraftUpdating(function () use (&$updatingFired) {
            $updatingFired = true;
        });

        Post::pivotDraftUpdated(function () use (&$updatedFired) {
            $updatedFired = true;
        });

        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);

        $post->status = Status::DRAFT;
        $post->save();

        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 5]);

        expect($updatingFired)->toBeTrue();
        expect($updatedFired)->toBeTrue();
    });
});

describe('Publishable pivot attributes work with morph pivots', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('stores pivot attribute changes in draft column for morph pivots', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $media = \Plank\Publisher\Tests\Helpers\Models\Media::factory()->create();

        // Attach while published
        $post->customMedia()->attach([$media->getKey()], ['order' => 1]);

        // Now draft the parent
        $post->status = Status::DRAFT;
        $post->save();

        // Update the pivot
        $post->customMedia()->updateExistingPivot($media->getKey(), ['order' => 5]);

        // Check that the draft column has the changes
        $pivot = $post->customMedia()->withPivot([
            'id',
            'order',
            'draft',
            'has_been_published',
        ])->first()->pivot;

        expect((int) $pivot->order)->toBe(1); // Real column should still be 1
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(5); // Draft should have the new value
    });
});
