<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Media;
use Plank\Publisher\Tests\Helpers\Models\Post;

describe('Publishable MorphToMany relationships can be managed via publishing status', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('attaches records correctly on publishable morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $media = Media::factory()->create();

        $post->media()->attach([$media->getKey()]);

        $pivot = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('attaches records correctly on publishable morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $media = Media::factory()->create();

        $post->media()->attach([$media->getKey()]);

        $pivot = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'has_been_published',
            'should_delete',
        ])->first()->pivot;

        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('detaches records correctly on publishable morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $media = Media::factory()->create();

        $post->media()->attach([$media->getKey()]);
        $post->media()->detach([$media->getKey()]);

        expect($post->media()->get())->toBeEmpty();
    });

    it('detaches records correctly on publishable morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $media = Media::factory()->create();

        $post->media()->attach([$media->getKey()]);
        $post->media()->detach([$media->getKey()]);

        expect($post->media()->get())->toBeEmpty();
    });

    it('toggles records correctly on publishable morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->media()->attach([$attached->getKey()]);
        $post->media()->toggle([$attached->getKey(), $notAttached->getKey()]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
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

    it('toggles records correctly on publishable morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->media()->attach([$attached->getKey()]);
        $post->media()->toggle([$attached->getKey(), $notAttached->getKey()]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->where('id', $notAttached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('syncs records correctly on publishable morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->media()->attach([$attached->getKey()]);
        $post->media()->sync([$notAttached->getKey()]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
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

    it('syncs records correctly on publishable morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->media()->attach([$attached->getKey()]);
        $post->media()->sync([$notAttached->getKey()]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->where('id', $notAttached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
    });

    it('syncs without detaching records correctly on publishable morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->media()->attach([$attached->getKey()]);
        $post->media()->syncWithoutDetaching([$notAttached->getKey()]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
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

    it('syncs without detaching records correctly on publishable morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->media()->attach([$attached->getKey()]);
        $post->media()->syncWithoutDetaching([$notAttached->getKey()]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
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

    it('syncs with pivot values records correctly on publishable morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->media()->attach([$attached->getKey()]);
        $post->media()->syncWithPivotValues([$notAttached->getKey()], [
            'collection' => 'downloads',
        ]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'collection',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->not->toContain($attached->id);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect($pivot->collection)->toBe('downloads');
    });

    it('syncs with pivot values records correctly on publishable morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->media()->attach([$attached->getKey()]);
        $post->media()->syncWithPivotValues([$notAttached->getKey()], [
            'collection' => 'downloads',
        ]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'collection',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($notAttached->id);

        $pivot = $posts->where('id', $notAttached->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect($pivot->collection)->toBe('downloads');
    });

    it('updates existing pivot records correctly on publishable morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Media::factory()->create();

        $post->media()->attach([$featured->getKey()]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'collection',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($featured->id);

        $pivot = $posts->where('id', $featured->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect($pivot->collection)->toBe('default');

        $originalPivotId = $pivot->id;

        $post->media()->updateExistingPivot($featured->getKey(), [
            'collection' => 'downloads',
        ]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'collection',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($featured->id);

        $pivot = $posts->where('id', $featured->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeTrue();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect($pivot->collection)->toBe('downloads');
        expect($pivot->id)->toBe($originalPivotId);
    });

    it('updates existing pivot records correctly on publishable morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Media::factory()->create();

        $post->media()->attach([$featured->getKey()]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'collection',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($featured->id);

        $pivot = $posts->where('id', $featured->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect($pivot->collection)->toBe('default');

        $originalPivotId = $pivot->id;

        $post->media()->updateExistingPivot($featured->getKey(), [
            'collection' => 'downloads',
        ]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
            'collection',
            'has_been_published',
            'should_delete',
        ])->get();

        expect($ids = $posts->pluck('id'))->toHaveCount(1);
        expect($ids)->toContain($featured->id);

        $pivot = $posts->where('id', $featured->id)->first()->pivot;
        expect((bool) $pivot->has_been_published)->toBeFalse();
        expect((bool) $pivot->should_delete)->toBeFalse();
        expect($pivot->collection)->toBe('downloads');
        expect($pivot->id)->toBe($originalPivotId);
    });

    it('updates the publishable morph pivot relations correctly when the parent is being published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attachedPublishedAndRemains = Media::factory()->create();
        $attachedPublishedAndWillBeDetached = Media::factory()->create();

        $post->media()->attach([
            $attachedPublishedAndRemains->getKey(),
            $attachedPublishedAndWillBeDetached->getKey(),
        ]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
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

        $attachedDraftAndRemains = Media::factory()->create();
        $attachedDraftAndWillBeDetached = Media::factory()->create();

        $post->media()->attach([
            $attachedDraftAndRemains->getKey(),
            $attachedDraftAndWillBeDetached->getKey(),
        ]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
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

        $post->media()->sync([$attachedPublishedAndRemains->getKey(), $attachedDraftAndRemains->getKey()]);

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
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

        $posts = $post->media()->withPivot([
            'id',
            'media_id',
            'mediable_id',
            'mediable_type',
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

    it('does not apply draft logic to the morph type column constraint', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $media = Media::factory()->create();
        $post->media()->attach([$media->getKey()]);

        // Get the SQL query for the relationship
        $sql = $post->media()->toRawSql();

        // The morph type constraint should be a simple equality check,
        // not wrapped in draft logic (which would include json_extract or status checks)
        expect($sql)->toContain('"mediable_type" = ')
            ->not->toMatch('/\(.*mediable_type.*status.*\)|json_extract.*mediable_type/i');
    });
});
