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

        $featured = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        // Attach while published
        $post->featured()->attach([$featured->getKey()], ['order' => 1]);

        // Now draft the parent
        $post->status = Status::DRAFT;
        $post->save();

        // Update the pivot
        $post->featured()->updateExistingPivot($featured->getKey(), ['order' => 5]);

        // When draft content is allowed, the pivot shows merged draft values
        $pivot = $post->featured()->withPivot(['order'])->first()->pivot;
        expect((int) $pivot->order)->toBe(5); // Shows draft value when draft content allowed
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(5);

        // Verify real column is unchanged by querying with draft content restricted
        Publisher::withoutDraftContent(function () use ($post) {
            $pivot = $post->featured()->withPivot(['order'])->first()->pivot;
            expect((int) $pivot->order)->toBe(1); // Shows real column value
        });
    });

    it('updates real columns directly when parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()], ['order' => 1]);
        $post->featured()->updateExistingPivot($featured->getKey(), ['order' => 5]);

        $pivot = $post->featured()->withPivot(['order'])->first()->pivot;

        expect((int) $pivot->order)->toBe(5); // Real column should be updated
        expect($pivot->draft)->toBeNull(); // No draft
    });

    it('updates real columns directly when parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->featured()->attach([$featured->getKey()], ['order' => 1]);
        $post->featured()->updateExistingPivot($featured->getKey(), ['order' => 5]);

        $pivot = $post->featured()->withPivot(['order'])->first()->pivot;

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
        $post->featured()->attach([$featured->getKey()], ['order' => 1]);

        // Draft and update
        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->updateExistingPivot($featured->getKey(), ['order' => 10]);

        // Publish
        $post->status = Status::PUBLISHED;
        $post->save();

        $pivot = $post->featured()->withPivot(['order'])->first()->pivot;

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
        $post->featured()->attach([$featured->getKey()], ['order' => 1]);

        // Draft and update
        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->updateExistingPivot($featured->getKey(), ['order' => 10]);

        // Revert
        $post->revert();

        $pivot = $post->featured()->withPivot(['order'])->first()->pivot;

        expect((int) $pivot->order)->toBe(1); // Real column still has original value
        expect($pivot->draft)->toBeNull(); // Draft is cleared
    });

    it('loads draft pivot values automatically when accessing pivot on draft parent with draft content allowed', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published
        $post->featured()->attach([$featured->getKey()], ['order' => 1]);

        // Draft and update
        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->updateExistingPivot($featured->getKey(), ['order' => 10]);

        // Reload the post and check the pivot value
        $post = Post::find($post->id);

        Publisher::allowDraftContent();

        // The draft column is auto-included, but order needs withPivot
        // The draft values are merged, so order comes from draft
        $pivot = $post->featured()->withPivot(['order'])->first()->pivot;

        // The pivot should automatically have draft values merged
        expect((int) $pivot->order)->toBe(10);
    });

    it('accumulates multiple draft updates', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        // Attach while published
        $post->featured()->attach([$featured->getKey()], ['order' => 1, 'paywall' => false]);

        // Draft and update multiple times
        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->updateExistingPivot($featured->getKey(), ['order' => 5]);
        $post->featured()->updateExistingPivot($featured->getKey(), ['paywall' => true]);

        // When draft content allowed, shows merged draft values
        $pivot = $post->featured()->withPivot(['order', 'paywall'])->first()->pivot;
        expect((int) $pivot->order)->toBe(5);
        expect((bool) $pivot->paywall)->toBeTrue();

        // Draft column contains the accumulated updates
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(5);
        expect($pivot->draft['paywall'])->toBeTrue();

        // Verify real columns are unchanged when draft content restricted
        Publisher::withoutDraftContent(function () use ($post) {
            $pivot = $post->featured()->withPivot(['order', 'paywall'])->first()->pivot;
            expect((int) $pivot->order)->toBe(1);
            expect((bool) $pivot->paywall)->toBeFalse();
        });
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

        $post->featured()->attach([$featured->getKey()], ['order' => 1]);

        $post->status = Status::DRAFT;
        $post->save();

        $post->featured()->updateExistingPivot($featured->getKey(), ['order' => 5]);

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
        $post->media()->attach([$media->getKey()], ['order' => 1]);

        // Now draft the parent
        $post->status = Status::DRAFT;
        $post->save();

        // Update the pivot
        $post->media()->updateExistingPivot($media->getKey(), ['order' => 5]);

        // When draft content allowed, shows merged draft values
        $pivot = $post->media()->withPivot(['order'])->first()->pivot;
        expect((int) $pivot->order)->toBe(5);
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(5);

        // Verify real column is unchanged when draft content restricted
        Publisher::withoutDraftContent(function () use ($post) {
            $pivot = $post->media()->withPivot(['order'])->first()->pivot;
            expect((int) $pivot->order)->toBe(1);
        });
    });
});

describe('Publishable pivot attributes work with custom pivot models', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('stores pivot attribute changes in draft column with custom pivot class', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        // Attach while published using the custom pivot relation
        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);

        // Now draft the parent
        $post->status = Status::DRAFT;
        $post->save();

        // Update the pivot
        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 5]);

        // When draft content allowed, shows merged draft values
        $pivot = $post->customFeatured()->withPivot(['order'])->first()->pivot;
        expect((int) $pivot->order)->toBe(5);
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(5);

        // Verify real column is unchanged when draft content restricted
        Publisher::withoutDraftContent(function () use ($post) {
            $pivot = $post->customFeatured()->withPivot(['order'])->first()->pivot;
            expect((int) $pivot->order)->toBe(1);
        });
    });

    it('publishes draft pivot attributes with custom pivot class', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);

        // Draft and update
        $post->status = Status::DRAFT;
        $post->save();

        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 10]);

        // Publish
        $post->status = Status::PUBLISHED;
        $post->save();

        $pivot = $post->customFeatured()->withPivot(['order'])->first()->pivot;

        expect((int) $pivot->order)->toBe(10); // Real column now has the draft value
        expect($pivot->draft)->toBeNull(); // Draft is cleared
    });

    it('reverts draft pivot attributes with custom pivot class', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1]);

        // Draft and update
        $post->status = Status::DRAFT;
        $post->save();

        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 10]);

        // Revert
        $post->revert();

        $pivot = $post->customFeatured()->withPivot(['order'])->first()->pivot;

        expect((int) $pivot->order)->toBe(1); // Real column still has original value
        expect($pivot->draft)->toBeNull(); // Draft is cleared
    });

    it('stores pivot attribute changes in draft column with custom morph pivot class', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $media = \Plank\Publisher\Tests\Helpers\Models\Media::factory()->create();

        // Attach while published using the custom morph pivot relation
        $post->customMedia()->attach([$media->getKey()], ['order' => 1]);

        // Now draft the parent
        $post->status = Status::DRAFT;
        $post->save();

        // Update the pivot
        $post->customMedia()->updateExistingPivot($media->getKey(), ['order' => 5]);

        // When draft content allowed, shows merged draft values
        $pivot = $post->customMedia()->withPivot(['order'])->first()->pivot;
        expect((int) $pivot->order)->toBe(5);
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(5);

        // Verify real column is unchanged when draft content restricted
        Publisher::withoutDraftContent(function () use ($post) {
            $pivot = $post->customMedia()->withPivot(['order'])->first()->pivot;
            expect((int) $pivot->order)->toBe(1);
        });
    });

    it('accumulates multiple draft updates with custom pivot class', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        // Attach while published
        $post->customFeatured()->attach([$featured->getKey()], ['order' => 1, 'paywall' => false]);

        // Draft and update multiple times
        $post->status = Status::DRAFT;
        $post->save();

        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['order' => 5]);
        $post->customFeatured()->updateExistingPivot($featured->getKey(), ['paywall' => true]);

        // When draft content allowed, shows merged draft values
        $pivot = $post->customFeatured()->withPivot(['order', 'paywall'])->first()->pivot;
        expect((int) $pivot->order)->toBe(5);
        expect((bool) $pivot->paywall)->toBeTrue();

        // Draft column contains the accumulated updates
        expect($pivot->draft)->toBeArray();
        expect($pivot->draft['order'])->toBe(5);
        expect($pivot->draft['paywall'])->toBeTrue();

        // Verify real columns are unchanged when draft content restricted
        Publisher::withoutDraftContent(function () use ($post) {
            $pivot = $post->customFeatured()->withPivot(['order', 'paywall'])->first()->pivot;
            expect((int) $pivot->order)->toBe(1);
            expect((bool) $pivot->paywall)->toBeFalse();
        });
    });
});
