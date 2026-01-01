<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;

beforeEach(function () {
    Publisher::allowDraftContent();
});

describe('Publishable pivot query methods with draft content allowed', function () {
    describe('wherePivot queries', function () {
        it('queries published pivot columns directly for published pivots', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['paywall' => true],
                $featured2->getKey() => ['paywall' => false],
            ]);

            $results = $post->featured()->wherePivot('paywall', true)->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($featured1->id);
        });

        it('queries draft pivot columns for unpublished pivots', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();

            // Attach while published
            $post->featured()->attach([
                $featured1->getKey() => ['order' => 1],
                $featured2->getKey() => ['order' => 2],
            ]);

            // Draft the parent and update pivots
            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->updateExistingPivot($featured1->getKey(), ['order' => 10]);

            // Query by draft value - should find featured1
            $results = $post->featured()->wherePivot('order', 10)->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($featured1->id);
        });

        it('queries with operators', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => 5],
                $featured2->getKey() => ['order' => 10],
            ]);

            $results = $post->featured()->wherePivot('order', '>', 7)->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($featured2->id);
        });

        it('does not use draft column for excluded columns like foreign keys', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured = Post::factory()->create();

            $post->featured()->attach([$featured->getKey()]);

            $results = $post->featured()->wherePivot('featured_id', $featured->getKey())->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($featured->id);
        });
    });

    describe('wherePivotIn queries', function () {
        it('queries published pivot columns with IN clause', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();
            $featured3 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => 1],
                $featured2->getKey() => ['order' => 2],
                $featured3->getKey() => ['order' => 3],
            ]);

            $results = $post->featured()->wherePivotIn('order', [1, 3])->get();

            expect($results)->toHaveCount(2);
            expect($results->pluck('id')->toArray())->toContain($featured1->id, $featured3->id);
        });

        it('queries draft pivot columns with IN clause for unpublished pivots', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => 1],
                $featured2->getKey() => ['order' => 2],
            ]);

            // Draft the parent and update pivots
            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->updateExistingPivot($featured1->getKey(), ['order' => 10]);
            $post->featured()->updateExistingPivot($featured2->getKey(), ['order' => 20]);

            $results = $post->featured()->wherePivotIn('order', [10, 20])->get();

            expect($results)->toHaveCount(2);
        });
    });

    describe('wherePivotNotIn queries', function () {
        it('excludes published pivot values with NOT IN clause', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();
            $featured3 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => 1],
                $featured2->getKey() => ['order' => 2],
                $featured3->getKey() => ['order' => 3],
            ]);

            $results = $post->featured()->wherePivotNotIn('order', [1, 3])->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($featured2->id);
        });
    });

    describe('wherePivotNull queries', function () {
        it('queries published pivot columns for null values', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => null],
                $featured2->getKey() => ['order' => 1],
            ]);

            $results = $post->featured()->wherePivotNull('order')->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($featured1->id);
        });

        // Note: Testing wherePivotNull for draft values is not supported because
        // JSON extraction cannot distinguish between "key exists with null value"
        // and "key doesn't exist" - both return NULL from json_extract().
    });

    describe('wherePivotNotNull queries', function () {
        it('queries published pivot columns for non-null values', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => null],
                $featured2->getKey() => ['order' => 1],
            ]);

            $results = $post->featured()->wherePivotNotNull('order')->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($featured2->id);
        });
    });

    describe('wherePivotBetween queries', function () {
        it('queries published pivot columns with BETWEEN clause', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();
            $featured3 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => 1],
                $featured2->getKey() => ['order' => 5],
                $featured3->getKey() => ['order' => 10],
            ]);

            $results = $post->featured()->wherePivotBetween('order', [2, 8])->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($featured2->id);
        });

        it('queries draft pivot columns with BETWEEN clause when unpublished', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => 1],
                $featured2->getKey() => ['order' => 2],
            ]);

            // Draft the parent and update pivots
            $post->status = Status::DRAFT;
            $post->save();

            $post->featured()->updateExistingPivot($featured1->getKey(), ['order' => 5]);
            $post->featured()->updateExistingPivot($featured2->getKey(), ['order' => 15]);

            $results = $post->featured()->wherePivotBetween('order', [1, 10])->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($featured1->id);
        });
    });

    describe('wherePivotNotBetween queries', function () {
        it('excludes published pivot values with NOT BETWEEN clause', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();
            $featured3 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => 1],
                $featured2->getKey() => ['order' => 5],
                $featured3->getKey() => ['order' => 10],
            ]);

            $results = $post->featured()->wherePivotNotBetween('order', [2, 8])->get();

            expect($results)->toHaveCount(2);
            expect($results->pluck('id')->toArray())->toContain($featured1->id, $featured3->id);
        });
    });

    describe('orWherePivot queries', function () {
        it('combines pivot conditions with OR', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();
            $featured3 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['paywall' => true, 'order' => 1],
                $featured2->getKey() => ['paywall' => false, 'order' => 5],
                $featured3->getKey() => ['paywall' => false, 'order' => 1],
            ]);

            $results = $post->featured()
                ->wherePivot('paywall', true)
                ->orWherePivot('order', 5)
                ->get();

            expect($results)->toHaveCount(2);
            expect($results->pluck('id')->toArray())->toContain($featured1->id, $featured2->id);
        });
    });

    describe('orWherePivotIn queries', function () {
        it('combines pivot IN conditions with OR', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();
            $featured3 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['order' => 1],
                $featured2->getKey() => ['order' => 5],
                $featured3->getKey() => ['order' => 10],
            ]);

            $results = $post->featured()
                ->wherePivot('order', 1)
                ->orWherePivotIn('order', [10, 15])
                ->get();

            expect($results)->toHaveCount(2);
            expect($results->pluck('id')->toArray())->toContain($featured1->id, $featured3->id);
        });
    });

    describe('orWherePivotNotIn queries', function () {
        it('combines pivot NOT IN conditions with OR', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured1 = Post::factory()->create();
            $featured2 = Post::factory()->create();
            $featured3 = Post::factory()->create();

            $post->featured()->attach([
                $featured1->getKey() => ['paywall' => true, 'order' => 1],
                $featured2->getKey() => ['paywall' => false, 'order' => 5],
                $featured3->getKey() => ['paywall' => false, 'order' => 10],
            ]);

            $results = $post->featured()
                ->wherePivot('paywall', true)
                ->orWherePivotNotIn('order', [1, 5])
                ->get();

            expect($results)->toHaveCount(2);
            expect($results->pluck('id')->toArray())->toContain($featured1->id, $featured3->id);
        });
    });

    describe('shouldUsePivotDraftColumn behavior', function () {
        it('returns false for foreign key columns', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured = Post::factory()->create();

            $post->featured()->attach([$featured->getKey()]);

            $results = $post->featured()->wherePivot('post_id', $post->getKey())->get();

            expect($results)->toHaveCount(1);
        });

        it('returns false for has_been_published column', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured = Post::factory()->create();

            $post->featured()->attach([$featured->getKey()]);

            $results = $post->featured()->wherePivot('has_been_published', true)->get();

            expect($results)->toHaveCount(1);
        });

        it('returns false for should_delete column', function () {
            $post = Post::factory()->create(['status' => Status::PUBLISHED]);
            $featured = Post::factory()->create();

            $post->featured()->attach([$featured->getKey()]);

            $results = $post->featured()->wherePivot('should_delete', false)->get();

            expect($results)->toHaveCount(1);
        });
    });
});

describe('Publishable pivot query methods with draft content restricted', function () {
    it('queries pivot columns directly when draft content is restricted', function () {
        Publisher::restrictDraftContent();

        $post = Post::factory()->create(['status' => Status::PUBLISHED]);
        // Featured posts must also be published to be visible when draft content is restricted
        $featured1 = Post::factory()->create(['status' => Status::PUBLISHED]);
        $featured2 = Post::factory()->create(['status' => Status::PUBLISHED]);

        $post->featured()->attach([
            $featured1->getKey() => ['paywall' => true],
            $featured2->getKey() => ['paywall' => false],
        ]);

        $results = $post->featured()->wherePivot('paywall', true)->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($featured1->id);
    });
});
