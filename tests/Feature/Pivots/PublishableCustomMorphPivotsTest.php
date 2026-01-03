<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Media;
use Plank\Publisher\Tests\Helpers\Models\Post;

describe('Publishable MorphToMany relationships using a custom pivot can be managed via publishing status', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    it('attaches records correctly on publishable custom morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $media = Media::factory()->create();

        $post->customMedia()->attach([$media->getKey()]);

        $pivot = $post->customMedia()->withPivot([
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

    it('attaches records correctly on publishable custom morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $media = Media::factory()->create();

        $post->customMedia()->attach([$media->getKey()]);

        $pivot = $post->customMedia()->withPivot([
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

    it('detaches records correctly on publishable custom morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $media = Media::factory()->create();

        $post->customMedia()->attach([$media->getKey()]);
        $post->customMedia()->detach([$media->getKey()]);

        expect($post->customMedia()->get())->toBeEmpty();
    });

    it('detaches records correctly on publishable custom morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $media = Media::factory()->create();

        $post->customMedia()->attach([$media->getKey()]);
        $post->customMedia()->detach([$media->getKey()]);

        expect($post->customMedia()->get())->toBeEmpty();
    });

    it('toggles records correctly on publishable custom morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->customMedia()->attach([$attached->getKey()]);
        $post->customMedia()->toggle([$attached->getKey(), $notAttached->getKey()]);

        $posts = $post->customMedia()->withPivot([
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

    it('toggles records correctly on publishable custom morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->customMedia()->attach([$attached->getKey()]);
        $post->customMedia()->toggle([$attached->getKey(), $notAttached->getKey()]);

        $posts = $post->customMedia()->withPivot([
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

    it('syncs records correctly on publishable custom morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->customMedia()->attach([$attached->getKey()]);
        $post->customMedia()->sync([$notAttached->getKey()]);

        $posts = $post->customMedia()->withPivot([
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

    it('syncs records correctly on publishable custom morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->customMedia()->attach([$attached->getKey()]);
        $post->customMedia()->sync([$notAttached->getKey()]);

        $posts = $post->customMedia()->withPivot([
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

    it('syncs without detaching records correctly on publishable custom morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->customMedia()->attach([$attached->getKey()]);
        $post->customMedia()->syncWithoutDetaching([$notAttached->getKey()]);

        $posts = $post->customMedia()->withPivot([
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

    it('syncs without detaching records correctly on publishable custom morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->customMedia()->attach([$attached->getKey()]);
        $post->customMedia()->syncWithoutDetaching([$notAttached->getKey()]);

        $posts = $post->customMedia()->withPivot([
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

    it('syncs with pivot values records correctly on publishable custom morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->customMedia()->attach([$attached->getKey()]);
        $post->customMedia()->syncWithPivotValues([$notAttached->getKey()], [
            'collection' => 'downloads',
        ]);

        $posts = $post->customMedia()->withPivot([
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

    it('syncs with pivot values records correctly on publishable custom morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $attached = Media::factory()->create();
        $notAttached = Media::factory()->create();

        $post->customMedia()->attach([$attached->getKey()]);
        $post->customMedia()->syncWithPivotValues([$notAttached->getKey()], [
            'collection' => 'downloads',
        ]);

        $posts = $post->customMedia()->withPivot([
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

    it('updates existing pivot records correctly on publishable custom morph pivots when the parent is published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $featured = Media::factory()->create();

        $post->customMedia()->attach([$featured->getKey()]);

        $posts = $post->customMedia()->withPivot([
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

        $post->customMedia()->updateExistingPivot($featured->getKey(), [
            'collection' => 'downloads',
        ]);

        $posts = $post->customMedia()->withPivot([
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

    it('updates existing pivot records correctly on publishable custom morph pivots when the parent has never been published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::DRAFT,
        ]);

        $featured = Media::factory()->create();

        $post->customMedia()->attach([$featured->getKey()]);

        $posts = $post->customMedia()->withPivot([
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

        $post->customMedia()->updateExistingPivot($featured->getKey(), [
            'collection' => 'downloads',
        ]);

        $posts = $post->customMedia()->withPivot([
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

    it('updates the publishable custom morph pivot relations correctly when the parent is being published', function () {
        /** @var Post $post */
        $post = Post::factory()->create([
            'status' => Status::PUBLISHED,
        ]);

        $attachedPublishedAndRemains = Media::factory()->create();
        $attachedPublishedAndWillBeDetached = Media::factory()->create();

        $post->customMedia()->attach([
            $attachedPublishedAndRemains->getKey(),
            $attachedPublishedAndWillBeDetached->getKey(),
        ]);

        $posts = $post->customMedia()->withPivot([
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

        $post->customMedia()->attach([
            $attachedDraftAndRemains->getKey(),
            $attachedDraftAndWillBeDetached->getKey(),
        ]);

        $posts = $post->customMedia()->withPivot([
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

        $post->customMedia()->sync([$attachedPublishedAndRemains->getKey(), $attachedDraftAndRemains->getKey()]);

        $posts = $post->customMedia()->withPivot([
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

        $posts = $post->customMedia()->withPivot([
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
});
