<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Media;
use Plank\Publisher\Tests\Helpers\Models\Post;

describe('Publishable pivot filtering is applied during eager loading', function () {
    describe('with draft content restricted', function () {
        beforeEach(function () {
            Publisher::restrictDraftContent();
        });

        it('filters unpublished pivots when eager loading via with()', function () {
            Publisher::allowDraftContent();

            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $published1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $published2 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $draft1 = Post::factory()->create(['status' => Status::PUBLISHED]);

            $post->featured()->attach([$published1->getKey(), $published2->getKey()]);

            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->attach([$draft1->getKey()]);

            Publisher::restrictDraftContent();

            $loaded = Post::with('featured')->find($post->getKey());

            expect($loaded->featured)->toHaveCount(2);
            expect($loaded->featured->pluck('id')->toArray())
                ->toContain($published1->id, $published2->id);
        });

        it('filters unpublished pivots when eager loading via load()', function () {
            Publisher::allowDraftContent();

            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $published1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $draft1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $draft2 = Post::factory()->create(['status' => Status::PUBLISHED]);

            $post->featured()->attach([$published1->getKey()]);

            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->attach([$draft1->getKey(), $draft2->getKey()]);

            Publisher::restrictDraftContent();

            $post = Post::find($post->getKey());
            $post->load('featured');

            expect($post->featured)->toHaveCount(1);
            expect($post->featured->first()->id)->toBe($published1->id);
        });

        it('filters unpublished pivots on morph relations when eager loading via with()', function () {
            Publisher::allowDraftContent();

            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $publishedMedia = Media::factory()->create();
            $draftMedia = Media::factory()->create();

            $post->media()->attach([$publishedMedia->getKey()]);

            $post->status = Status::DRAFT;
            $post->save();

            $post->media()->attach([$draftMedia->getKey()]);

            Publisher::restrictDraftContent();

            $loaded = Post::with('media')->find($post->getKey());

            expect($loaded->media)->toHaveCount(1);
            expect($loaded->media->first()->id)->toBe($publishedMedia->id);
        });

        it('returns consistent results between lazy and eager loading', function () {
            Publisher::allowDraftContent();

            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $published1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $published2 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $draft1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $draft2 = Post::factory()->create(['status' => Status::PUBLISHED]);

            $post->featured()->attach([$published1->getKey(), $published2->getKey()]);

            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->attach([$draft1->getKey(), $draft2->getKey()]);

            Publisher::restrictDraftContent();

            // Lazy load
            $lazyPost = Post::find($post->getKey());
            $lazyCount = $lazyPost->featured->count();

            // Eager load via with()
            $eagerPost = Post::with('featured')->find($post->getKey());
            $eagerCount = $eagerPost->featured->count();

            // Eager load via load()
            $loadPost = Post::find($post->getKey());
            $loadPost->load('featured');
            $loadCount = $loadPost->featured->count();

            expect($lazyCount)->toBe(2);
            expect($eagerCount)->toBe($lazyCount);
            expect($loadCount)->toBe($lazyCount);
        });

        it('filters pivots correctly across multiple eager loaded models', function () {
            Publisher::allowDraftContent();

            $post1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $post2 = Post::factory()->create(['status' => Status::PUBLISHED]);

            $published1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $published2 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $draft1 = Post::factory()->create(['status' => Status::PUBLISHED]);

            $post1->featured()->attach([$published1->getKey()]);
            $post2->featured()->attach([$published2->getKey()]);

            $post1->status = Status::DRAFT;
            $post1->save();
            $post1->featured()->attach([$draft1->getKey()]);

            Publisher::restrictDraftContent();

            $posts = Post::with('featured')
                ->whereIn('id', [$post1->getKey(), $post2->getKey()])
                ->get();

            $loadedPost1 = $posts->where('id', $post1->id)->first();
            $loadedPost2 = $posts->where('id', $post2->id)->first();

            expect($loadedPost1->featured)->toHaveCount(1);
            expect($loadedPost1->featured->first()->id)->toBe($published1->id);
            expect($loadedPost2->featured)->toHaveCount(1);
            expect($loadedPost2->featured->first()->id)->toBe($published2->id);
        });
    });

    describe('with draft content allowed', function () {
        beforeEach(function () {
            Publisher::allowDraftContent();
        });

        it('filters pivots marked for deletion when eager loading via with()', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured2 = Post::factory()->create(['status' => Status::PUBLISHED]);

            $post->featured()->attach([$featured1->getKey(), $featured2->getKey()]);

            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->detach([$featured2->getKey()]);

            $loaded = Post::with('featured')->find($post->getKey());

            expect($loaded->featured)->toHaveCount(1);
            expect($loaded->featured->first()->id)->toBe($featured1->id);
        });

        it('filters pivots marked for deletion when eager loading via load()', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured2 = Post::factory()->create(['status' => Status::PUBLISHED]);

            $post->featured()->attach([$featured1->getKey(), $featured2->getKey()]);

            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->detach([$featured1->getKey()]);

            $post = Post::find($post->getKey());
            $post->load('featured');

            expect($post->featured)->toHaveCount(1);
            expect($post->featured->first()->id)->toBe($featured2->id);
        });

        it('returns consistent results between lazy and eager loading', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured2 = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured3 = Post::factory()->create(['status' => Status::PUBLISHED]);

            $post->featured()->attach([
                $featured1->getKey(),
                $featured2->getKey(),
                $featured3->getKey(),
            ]);

            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->detach([$featured3->getKey()]);

            // Lazy load
            $lazyPost = Post::find($post->getKey());
            $lazyCount = $lazyPost->featured->count();

            // Eager load via with()
            $eagerPost = Post::with('featured')->find($post->getKey());
            $eagerCount = $eagerPost->featured->count();

            // Eager load via load()
            $loadPost = Post::find($post->getKey());
            $loadPost->load('featured');
            $loadCount = $loadPost->featured->count();

            expect($lazyCount)->toBe(2);
            expect($eagerCount)->toBe($lazyCount);
            expect($loadCount)->toBe($lazyCount);
        });

        it('shows all non-deleted pivots including unpublished during eager loading', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $published = Post::factory()->create(['status' => Status::PUBLISHED]);

            $post->featured()->attach([$published->getKey()]);

            $post->status = Status::DRAFT;
            $post->save();

            $draft = Post::factory()->create(['status' => Status::PUBLISHED]);
            $post->featured()->attach([$draft->getKey()]);

            $loaded = Post::with('featured')->find($post->getKey());

            expect($loaded->featured)->toHaveCount(2);
            expect($loaded->featured->pluck('id')->toArray())
                ->toContain($published->id, $draft->id);
        });
    });

    describe('with draft pivot constraints restricted', function () {
        it('does not apply pivot filtering when pivot constraints are restricted', function () {
            Publisher::restrictDraftContent();
            Publisher::restrictDraftPivotConstraints();

            $post = Post::factory()->create(['status' => Status::PUBLISHED]);

            Publisher::allowDraftContent();

            $published = Post::factory()->create(['status' => Status::PUBLISHED]);
            $draft = Post::factory()->create(['status' => Status::PUBLISHED]);

            $post->featured()->attach([$published->getKey()]);

            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->attach([$draft->getKey()]);

            Publisher::restrictDraftContent();
            Publisher::restrictDraftPivotConstraints();

            $loaded = Post::with('featured')->find($post->getKey());

            // With pivot constraints restricted, no filtering is applied
            expect($loaded->featured)->toHaveCount(2);
        });
    });
});
