<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\Section;

beforeEach(function () {
    Publisher::allowDraftContent();
});

describe('withQueuedDeletes query scope', function () {
    it('includes models queued for deletion in results', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);

        $section1 = Section::factory()->create([
            'post_id' => $post->id,
            'status' => Status::PUBLISHED,
        ]);

        $section2 = Section::factory()->create([
            'post_id' => $post->id,
            'status' => Status::PUBLISHED,
        ]);

        // Unpublish the post so sections can be queued for delete
        $post->status = Status::DRAFT;
        $post->save();

        // Queue section2 for deletion
        $section2->refresh();
        $section2->delete();

        expect($section2->should_delete)->toBeTrue();

        // Default query excludes queued deletes
        $defaultResults = Section::query()->get();
        expect($defaultResults)->toHaveCount(1);
        expect($defaultResults->first()->id)->toBe($section1->id);

        // withQueuedDeletes includes them
        $results = Section::query()->withQueuedDeletes()->get();
        expect($results)->toHaveCount(2);
        expect($results->pluck('id')->toArray())->toContain($section1->id, $section2->id);
    });

    it('removes the global scope entirely', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);

        Section::factory()->create([
            'post_id' => $post->id,
            'status' => Status::PUBLISHED,
        ]);

        $section2 = Section::factory()->create([
            'post_id' => $post->id,
            'status' => Status::PUBLISHED,
        ]);

        // Unpublish and queue for delete
        $post->status = Status::DRAFT;
        $post->save();

        $section2->refresh();
        $section2->delete();

        // withQueuedDeletes should return all sections regardless of should_delete status
        $results = Section::query()->withQueuedDeletes()->get();
        expect($results)->toHaveCount(2);
    });
});

describe('onlyQueuedDeletes query scope', function () {
    it('returns only models queued for deletion', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);

        $section1 = Section::factory()->create([
            'post_id' => $post->id,
            'status' => Status::PUBLISHED,
        ]);

        $section2 = Section::factory()->create([
            'post_id' => $post->id,
            'status' => Status::PUBLISHED,
        ]);

        $section3 = Section::factory()->create([
            'post_id' => $post->id,
            'status' => Status::PUBLISHED,
        ]);

        // Unpublish the post so sections can be queued for delete
        $post->status = Status::DRAFT;
        $post->save();

        // Queue section2 and section3 for deletion
        $section2->refresh();
        $section2->delete();

        $section3->refresh();
        $section3->delete();

        // onlyQueuedDeletes should return only the queued ones
        $results = Section::query()->onlyQueuedDeletes()->get();
        expect($results)->toHaveCount(2);
        expect($results->pluck('id')->toArray())->toContain($section2->id, $section3->id);
        expect($results->pluck('id')->toArray())->not->toContain($section1->id);
    });

    it('returns empty collection when no models are queued for deletion', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);

        Section::factory(3)->create([
            'post_id' => $post->id,
            'status' => Status::PUBLISHED,
        ]);

        $results = Section::query()->onlyQueuedDeletes()->get();
        expect($results)->toBeEmpty();
    });

    it('can be combined with other query constraints', function () {
        $post = Post::factory()->create(['status' => Status::PUBLISHED]);

        $section1 = Section::factory()->create([
            'post_id' => $post->id,
            'heading' => 'Keep This',
            'status' => Status::PUBLISHED,
        ]);

        $section2 = Section::factory()->create([
            'post_id' => $post->id,
            'heading' => 'Delete This',
            'status' => Status::PUBLISHED,
        ]);

        $section3 = Section::factory()->create([
            'post_id' => $post->id,
            'heading' => 'Delete That',
            'status' => Status::PUBLISHED,
        ]);

        // Unpublish and queue for deletion
        $post->status = Status::DRAFT;
        $post->save();

        $section2->refresh();
        $section2->delete();

        $section3->refresh();
        $section3->delete();

        // Query with additional constraint
        $results = Section::query()
            ->onlyQueuedDeletes()
            ->where('heading', 'like', 'Delete%')
            ->get();

        expect($results)->toHaveCount(2);
    });
});
