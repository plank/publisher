<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Media;
use Plank\Publisher\Tests\Helpers\Models\Post;

describe('Relation existence queries when the pivot table is already joined on the parent query', function () {
    beforeEach(function () {
        Publisher::allowDraftContent();
    });

    describe('PublishableBelongsToMany', function () {
        it('aliases the pivot table in the existence subquery to avoid ambiguous columns', function () {
            $postA = Post::factory()->create(['status' => Status::PUBLISHED]);
            $postB = Post::factory()->create(['status' => Status::PUBLISHED]);
            $postC = Post::factory()->create(['status' => Status::PUBLISHED]);

            // postA features postB, postB features postC
            $postA->featured()->attach($postB->getKey());
            $postB->featured()->attach($postC->getKey());

            // query with a join on the pivot table, then add a relationship existence
            // check. Without the fix, getRelationExistenceQuery() joins the same pivot
            // table again without an alias, causing ambiguous column references.
            $query = Post::query()
                ->select('posts.*')
                ->join('post_post', 'posts.id', '=', 'post_post.featured_id')
                ->has('featured')
                ->distinct();

            // Verify the pivot table is aliased in the subquery
            expect($query->toSql())->toContain('"post_post" as ');

            $results = $query->get();

            // postB: featured by postA (join ✓), has featured postC (has ✓) → included
            // postC: featured by postB (join ✓), has no featured posts (has ✗) → excluded
            // postA: not featured by anyone (join ✗) → excluded
            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($postB->id);
        });

        it('aliases the pivot table in the non-existence subquery to avoid ambiguous columns', function () {
            $postA = Post::factory()->create(['status' => Status::PUBLISHED]);
            $postB = Post::factory()->create(['status' => Status::PUBLISHED]);
            $postC = Post::factory()->create(['status' => Status::PUBLISHED]);

            $postA->featured()->attach($postB->getKey());
            $postB->featured()->attach($postC->getKey());

            // doesntHave builds a WHERE NOT EXISTS subquery
            $query = Post::query()
                ->select('posts.*')
                ->join('post_post', 'posts.id', '=', 'post_post.featured_id')
                ->doesntHave('featured')
                ->distinct();

            expect($query->toSql())->toContain('"post_post" as ');

            $results = $query->get();

            // postB: featured by postA (join ✓), has featured posts (doesntHave ✗)
            // postC: featured by postB (join ✓), has no featured posts (doesntHave ✓)
            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($postC->id);
        });
    });

    describe('PublishableMorphToMany', function () {
        it('aliases the pivot table in the existence subquery to avoid ambiguous columns', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $media = Media::factory()->create();

            $post->media()->attach([$media->getKey()]);

            // Join the morph pivot table on the outer query, then add has('media')
            // which triggers another join on the same table via getRelationExistenceQuery.
            $query = Post::query()
                ->select('posts.*')
                ->join('mediables', 'posts.id', '=', 'mediables.mediable_id')
                ->has('media')
                ->distinct();

            // Verify the pivot table is aliased and the morph type constraint
            // uses the alias
            $sql = $query->toSql();
            expect($sql)->toContain('"mediables" as ');

            $results = $query->get();

            // $post: in mediables (join ✓), has media (has ✓) → included
            // $otherPost: not in mediables (join ✗) → excluded
            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($post->id);
        });
    });

    describe('without the pivot table pre-joined', function () {
        it('still works normally for has queries without a pre-existing pivot join', function () {
            $postA = Post::factory()->create(['status' => Status::PUBLISHED]);
            $postB = Post::factory()->create(['status' => Status::PUBLISHED]);

            $postA->featured()->attach($postB->getKey());

            // Standard has() without a pre-existing join should not alias
            $query = Post::query()->has('featured');

            // The pivot table should NOT be aliased (no conflict to resolve)
            expect($query->toSql())->not->toContain('"post_post" as ');

            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($postA->id);
        });

        it('still works normally for doesntHave queries without a pre-existing pivot join', function () {
            $postA = Post::factory()->create(['status' => Status::PUBLISHED]);
            $postB = Post::factory()->create(['status' => Status::PUBLISHED]);

            $postA->featured()->attach($postB->getKey());

            $results = Post::query()->doesntHave('featured')->get();

            // postB has no featured posts
            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($postB->id);
        });
    });
});
