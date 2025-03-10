<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Media;
use Plank\Publisher\Tests\Helpers\Models\Post;

describe('It queries publishable pivots correctly', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();

        /** @var Post $post */
        $post = Post::factory()->create([
            'title' => 'First Post!',
            'status' => Status::PUBLISHED,
        ]);

        $postAttachedPublishedAndRemains = Post::factory()->create([
            'title' => 'Attached Published And Remains',
            'status' => Status::PUBLISHED,
        ]);

        $postAttachedPublishedAndWillBeDetached = Post::factory()->create([
            'title' => 'Attached Published and Will be Detached',
            'status' => Status::PUBLISHED,
        ]);

        $mediaAttachedPublishedAndRemains = Media::factory()->create([
            'title' => 'Attached Published And Remains',
        ]);
        $mediaAttachedPublishedAndWillBeDetached = Media::factory()->create([
            'title' => 'Attached Published and Will be Detached',
        ]);

        $post->featured()->attach([
            $postAttachedPublishedAndRemains->getKey(),
            $postAttachedPublishedAndWillBeDetached->getKey(),
        ]);

        $post->media()->attach([
            $mediaAttachedPublishedAndRemains->getKey(),
            $mediaAttachedPublishedAndWillBeDetached->getKey(),
        ]);

        $post->title = 'First Post in Draft!';
        $post->status = Status::DRAFT;
        $post->save();

        $postAttachedDraftAndRemains = Post::factory()->create([
            'title' => 'Attached Draft And Remains',
            'status' => Status::PUBLISHED,
        ]);

        $postAttachedDraftAndWillBeDetached = Post::factory()->create([
            'title' => 'Attached Draft and Will be Detached',
            'status' => Status::PUBLISHED,
        ]);

        $mediaAttachedDraftAndRemains = Media::factory()->create([
            'title' => 'Attached Draft And Remains',
        ]);

        $mediaAttachedDraftAndWillBeDetached = Media::factory()->create([
            'title' => 'Attached Draft and Will be Detached',
        ]);

        $post->featured()->attach([
            $postAttachedDraftAndRemains->getKey(),
            $postAttachedDraftAndWillBeDetached->getKey(),
        ]);

        $post->media()->attach([
            $mediaAttachedDraftAndRemains->getKey(),
            $mediaAttachedDraftAndWillBeDetached->getKey(),
        ]);

        $post->featured()->sync([$postAttachedPublishedAndRemains->getKey(), $postAttachedDraftAndRemains->getKey()]);
        $post->media()->sync([$mediaAttachedPublishedAndRemains->getKey(), $mediaAttachedDraftAndRemains->getKey()]);

        Publisher::draftContentRestricted();
    });

    it('queries models through publishable pivots when allowing draft content', function () {
        Publisher::withDraftContent(function () {
            $post = Post::query()
                ->where('title', 'First Post in Draft!')
                ->first();

            expect($featured = $post->featured()->get())->toHaveCount(2);

            expect($featured->pluck('title'))
                ->toContain('Attached Published And Remains')
                ->toContain('Attached Draft And Remains');
        });
    });

    it('queries models through publishable custom pivots when allowing draft content', function () {
        Publisher::withDraftContent(function () {
            $post = Post::query()
                ->where('title', 'First Post in Draft!')
                ->first();

            expect($featured = $post->customFeatured()->get())->toHaveCount(2);

            expect($featured->pluck('title'))
                ->toContain('Attached Published And Remains')
                ->toContain('Attached Draft And Remains');
        });
    });

    it('queries models through publishable morph pivots when allowing draft content', function () {
        Publisher::withDraftContent(function () {
            $post = Post::query()
                ->where('title', 'First Post in Draft!')
                ->first();

            expect($featured = $post->media()->get())->toHaveCount(2);

            expect($featured->pluck('title'))
                ->toContain('Attached Published And Remains')
                ->toContain('Attached Draft And Remains');
        });
    });

    it('queries models through publishable custom morph pivots when allowing draft content', function () {
        Publisher::withDraftContent(function () {
            $post = Post::query()
                ->where('title', 'First Post in Draft!')
                ->first();

            expect($featured = $post->customMedia()->get())->toHaveCount(2);

            expect($featured->pluck('title'))
                ->toContain('Attached Published And Remains')
                ->toContain('Attached Draft And Remains');
        });
    });

    it('queries models through publishable pivots when draft content is blocked', function () {
        Publisher::withoutDraftContent(function () {
            $post = Post::query()
                ->where('title', 'First Post!')
                ->first();

            expect($featured = $post->featured()->get())->toHaveCount(2);

            expect($featured->pluck('title'))
                ->toContain('Attached Published And Remains')
                ->toContain('Attached Published and Will be Detached');
        });
    });

    it('queries models through publishable custom pivots when draft content is blocked', function () {
        Publisher::withoutDraftContent(function () {
            $post = Post::query()
                ->where('title', 'First Post!')
                ->first();

            expect($featured = $post->customFeatured()->get())->toHaveCount(2);

            expect($featured->pluck('title'))
                ->toContain('Attached Published And Remains')
                ->toContain('Attached Published and Will be Detached');
        });
    });

    it('queries models through publishable morph pivots when draft content is blocked', function () {
        Publisher::withoutDraftContent(function () {
            $post = Post::query()
                ->where('title', 'First Post!')
                ->first();

            expect($featured = $post->media()->get())->toHaveCount(2);

            expect($featured->pluck('title'))
                ->toContain('Attached Published And Remains')
                ->toContain('Attached Published and Will be Detached');
        });
    });

    it('queries models through publishable custom morph pivots when draft content is blocked', function () {
        Publisher::withoutDraftContent(function () {
            $post = Post::query()
                ->where('title', 'First Post!')
                ->first();

            expect($featured = $post->customMedia()->get())->toHaveCount(2);

            expect($featured->pluck('title'))
                ->toContain('Attached Published And Remains')
                ->toContain('Attached Published and Will be Detached');
        });
    });
});
