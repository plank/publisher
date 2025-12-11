<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Exceptions\RevertException;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Media;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

describe('Revert basic functionality', function () {
    it('reverts draft attribute changes to published values', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'body' => 'Original body content.',
            'status' => Status::PUBLISHED,
        ]);

        expect($post->status)->toBe(Status::PUBLISHED);
        expect($post->hasEverBeenPublished())->toBeTrue();

        $post->update(['status' => 'draft']);
        $post->update([
            'title' => 'Updated Title',
            'body' => 'Updated body content.',
        ]);

        expect($post->status)->toBe(Status::DRAFT);

        $post->revert();

        expect($post->status)->toBe(Status::PUBLISHED);
        expect($post->draft)->toBeNull();
        expect($post->title)->toBe('Original Title');
        expect($post->body)->toBe('Original body content.');
    });

    it('reverts multiple attribute changes at once', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'body' => 'Original body.',
            'status' => Status::PUBLISHED,
        ]);

        $post->update(['status' => 'draft']);
        $post->update([
            'title' => 'New Title',
            'slug' => 'new-slug',
            'body' => 'New body.',
        ]);

        Publisher::allowDraftContent();

        expect($post->title)->toBe('New Title');
        expect($post->slug)->toBe('new-slug');
        expect($post->body)->toBe('New body.');

        $post->revert();

        expect($post->title)->toBe('Original Title');
        expect($post->slug)->toBe('original-slug');
        expect($post->body)->toBe('Original body.');
    });

    it('throws exception when reverting content that was never published', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'body' => 'Draft content.',
            'status' => Status::DRAFT,
        ]);

        expect($post->hasEverBeenPublished())->toBeFalse();

        $post->revert();
    })->throws(RevertException::class);

    it('resets the should_delete flag when reverting', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Published Post',
            'slug' => 'published-post',
            'body' => 'Content.',
            'status' => Status::PUBLISHED,
        ]);

        $post->update(['status' => 'draft']);

        // Simulate marking for deletion
        $post->{$post->shouldDeleteColumn()} = true;
        $post->save();

        expect($post->{$post->shouldDeleteColumn()})->toBeTrue();

        $post->revert();

        expect($post->{$post->shouldDeleteColumn()})->toBeFalse();
    });

    it('sets the workflow status back to published when reverting', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Published Post',
            'slug' => 'published-post',
            'body' => 'Content.',
            'status' => Status::PUBLISHED,
        ]);

        $post->update(['status' => 'draft']);

        expect($post->status)->toBe(Status::DRAFT);

        $post->revert();

        expect($post->status)->toBe(Status::PUBLISHED);
        expect($post->isPublished())->toBeTrue();
    });
});

describe('Revert with publishable BelongsToMany pivots', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('deletes draft-only pivots when reverting', function () {
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Unpublish to make changes in draft
        $post->update(['status' => 'draft']);

        // Attach a featured post while in draft mode
        $post->featured()->attach([$featured->getKey()]);

        $pivot = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeFalse();

        // Revert should delete this draft-only pivot
        $post->revert();

        expect($post->featured()->get())->toBeEmpty();
    });

    it('restores published pivots marked for deletion when reverting', function () {
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        // Attach while published (pivot becomes published)
        $post->featured()->attach([$featured->getKey()]);

        $pivot = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeTrue();

        // Unpublish the post
        $post->update(['status' => 'draft']);

        // Detach the featured post (marks for deletion since already published)
        $post->featured()->detach([$featured->getKey()]);

        // In draft mode, the pivot should be marked for deletion, not actually deleted
        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toBeEmpty();

        // Revert should restore the pivot
        $post->revert();

        $restoredPivot = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->first();

        expect($restoredPivot)->not->toBeNull();
        expect((bool) $restoredPivot->pivot->has_been_published)->toBeTrue();
        expect((bool) $restoredPivot->pivot->should_delete)->toBeFalse();
    });

    it('handles mixed draft and published pivots correctly when reverting', function () {
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $publishedFeatured = Post::factory()->create();
        $draftFeatured = Post::factory()->create();

        // Attach while published
        $post->featured()->attach([$publishedFeatured->getKey()]);

        // Unpublish and attach another
        $post->update(['status' => 'draft']);
        $post->featured()->attach([$draftFeatured->getKey()]);

        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(2);

        // Revert should keep published pivot and delete draft-only pivot
        $post->revert();

        $posts = $post->featured()->withPivot([
            'id',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($posts)->toHaveCount(1);
        expect($posts->first()->id)->toBe($publishedFeatured->id);
        expect((bool) $posts->first()->pivot->has_been_published)->toBeTrue();
    });

    it('reverts sync changes to pivots', function () {
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $originalFeatured = Post::factory()->create();
        $newFeatured = Post::factory()->create();

        // Attach original while published
        $post->featured()->attach([$originalFeatured->getKey()]);

        // Unpublish and sync to new featured
        $post->update(['status' => 'draft']);
        $post->featured()->sync([$newFeatured->getKey()]);

        // In draft mode, we have only newFeatured visible
        $posts = $post->featured()->get();
        expect($posts)->toHaveCount(1);
        expect($posts->first()->id)->toBe($newFeatured->id);

        // Revert should restore original and remove new
        $post->revert();

        $posts = $post->featured()->get();
        expect($posts)->toHaveCount(1);
        expect($posts->first()->id)->toBe($originalFeatured->id);
    });
});

describe('Revert with publishable MorphToMany pivots', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('deletes draft-only morph pivots when reverting', function () {
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $media = Media::factory()->create();

        // Unpublish to make changes in draft
        $post->update(['status' => 'draft']);

        // Attach media while in draft mode
        $post->media()->attach([$media->getKey()]);

        $attachedMedia = $post->media()->withPivot([
            'has_been_published',
            'should_delete',
        ])->first();

        expect($attachedMedia)->not->toBeNull();
        expect((bool) $attachedMedia->pivot->has_been_published)->toBeFalse();

        // Revert should delete this draft-only pivot
        $post->revert();

        expect($post->media()->get())->toBeEmpty();
    });

    it('restores published morph pivots marked for deletion when reverting', function () {
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $media = Media::factory()->create();

        // Attach while published (pivot becomes published)
        $post->media()->attach([$media->getKey()]);

        $attachedMedia = $post->media()->withPivot([
            'has_been_published',
            'should_delete',
        ])->first();

        expect((bool) $attachedMedia->pivot->has_been_published)->toBeTrue();

        // Unpublish and detach
        $post->update(['status' => 'draft']);
        $post->media()->detach([$media->getKey()]);

        // Media should no longer be visible in draft mode
        expect($post->media()->get())->toBeEmpty();

        // Revert should restore the pivot
        $post->revert();

        $restoredMedia = $post->media()->withPivot([
            'has_been_published',
            'should_delete',
        ])->first();

        expect($restoredMedia)->not->toBeNull();
        expect((bool) $restoredMedia->pivot->has_been_published)->toBeTrue();
        expect((bool) $restoredMedia->pivot->should_delete)->toBeFalse();
    });

    it('handles mixed draft and published morph pivots correctly when reverting', function () {
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $publishedMedia = Media::factory()->create();
        $draftMedia = Media::factory()->create();

        // Attach while published
        $post->media()->attach([$publishedMedia->getKey()]);

        // Unpublish and attach another
        $post->update(['status' => 'draft']);
        $post->media()->attach([$draftMedia->getKey()]);

        $media = $post->media()->withPivot([
            'has_been_published',
            'should_delete',
        ])->get();

        expect($media)->toHaveCount(2);

        // Revert should keep published pivot and delete draft-only pivot
        $post->revert();

        $media = $post->media()->withPivot([
            'has_been_published',
            'should_delete',
        ])->get();

        expect($media)->toHaveCount(1);
        expect($media->first()->id)->toBe($publishedMedia->id);
        expect((bool) $media->first()->pivot->has_been_published)->toBeTrue();
    });
});

describe('Revert with custom pivot classes', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('deletes draft-only custom pivots when reverting', function () {
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Post::factory()->create();

        $post->update(['status' => 'draft']);
        $post->customFeatured()->attach([$featured->getKey()]);

        $pivot = $post->customFeatured()->withPivot([
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeFalse();

        $post->revert();

        expect($post->customFeatured()->get())->toBeEmpty();
    });

    it('deletes draft-only custom morph pivots when reverting', function () {
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $media = Media::factory()->create();

        $post->update(['status' => 'draft']);
        $post->customMedia()->attach([$media->getKey()]);

        $attachedMedia = $post->customMedia()->withPivot([
            'has_been_published',
            'should_delete',
        ])->first();

        expect((bool) $attachedMedia->pivot->has_been_published)->toBeFalse();

        $post->revert();

        expect($post->customMedia()->get())->toBeEmpty();
    });
});

describe('Revert persists changes correctly', function () {
    it('persists reverted state to the database', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'body' => 'Original body.',
            'status' => Status::PUBLISHED,
        ]);

        $postId = $post->id;

        $post->update(['status' => 'draft']);
        $post->update(['title' => 'Updated Title']);

        $post->revert();

        // Refresh from database
        $freshPost = Post::find($postId);

        expect($freshPost->title)->toBe('Original Title');
        expect($freshPost->status)->toBe(Status::PUBLISHED);
        expect($freshPost->draft)->toBeNull();
        expect($freshPost->{$freshPost->shouldDeleteColumn()})->toBeFalse();
    });

    it('can be called multiple times without error', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'body' => 'Original body.',
            'status' => Status::PUBLISHED,
        ]);

        $post->update(['status' => 'draft']);
        $post->update(['title' => 'Updated Title']);

        $post->revert();

        expect($post->title)->toBe('Original Title');
        expect($post->status)->toBe(Status::PUBLISHED);

        // Calling revert again should work without error
        $post->revert();

        expect($post->title)->toBe('Original Title');
        expect($post->status)->toBe(Status::PUBLISHED);
    });
});

describe('Revert events', function () {
    it('fires reverting event before reverting', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'body' => 'Original body.',
            'status' => Status::PUBLISHED,
        ]);

        $post->update(['status' => 'draft']);
        $post->update(['title' => 'Updated Title']);

        $revertingFired = false;
        $titleDuringReverting = null;

        Post::reverting(function (Post $model) use (&$revertingFired, &$titleDuringReverting) {
            $revertingFired = true;
            // At this point, the model still has draft content (before refresh)
            $titleDuringReverting = $model->title;
        });

        $post->revert();

        expect($revertingFired)->toBeTrue();
        // The reverting event fires before the model is refreshed, so it still has the draft title
        expect($titleDuringReverting)->toBe('Updated Title');
    });

    it('fires reverted event after reverting', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'body' => 'Original body.',
            'status' => Status::PUBLISHED,
        ]);

        $post->update(['status' => 'draft']);
        $post->update(['title' => 'Updated Title']);

        $revertedFired = false;
        $statusDuringReverted = null;

        Post::reverted(function (Post $model) use (&$revertedFired, &$statusDuringReverted) {
            $revertedFired = true;
            $statusDuringReverted = $model->status;
        });

        $post->revert();

        expect($revertedFired)->toBeTrue();
        expect($statusDuringReverted)->toBe(Status::PUBLISHED);
        expect($post->title)->toBe('Original Title');
    });

    it('does not fire publishing or published events while reverting', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'body' => 'Original body.',
            'status' => Status::PUBLISHED,
        ]);

        $post->update(['status' => 'draft']);
        $post->update(['title' => 'Updated Title']);

        $publishingFired = false;
        $publishedFired = false;

        Post::publishing(function (Post $model) use (&$publishingFired) {
            $publishingFired = true;
        });

        Post::published(function (Post $model) use (&$publishedFired) {
            $publishedFired = true;
        });

        $post->revert();

        expect($publishingFired)->toBeFalse();
        expect($publishedFired)->toBeFalse();
    });
});
