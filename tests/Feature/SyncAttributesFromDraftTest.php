<?php

use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

beforeEach(function () {
    Publisher::allowDraftContent();
});

describe('syncAttributesFromDraft handles edge cases gracefully', function () {
    it('skips keys in draft that do not exist in model attributes', function () {
        // Create a published post first (no draft)
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'My First Post',
            'slug' => 'my-first-post',
            'body' => 'This is the body of my first post.',
            'status' => 'published',
        ]);

        // Manually set a draft with an extra key that doesn't exist in the model's attributes
        // This simulates data corruption, manual DB edits, or schema changes
        \Illuminate\Support\Facades\DB::table('posts')
            ->where('id', $post->id)
            ->update([
                'status' => 'draft',
                'draft' => json_encode([
                    'title' => 'Draft Title',
                    'slug' => 'draft-slug',
                    'body' => 'Draft body.',
                    'nonexistent_column' => 'some value', // Key that doesn't exist in model attributes
                ]),
            ]);

        // Should not throw - extra keys are skipped
        $retrieved = Post::query()
            ->withoutGlobalScopes()
            ->find($post->id);

        // Valid draft values are synced
        expect($retrieved->title)->toBe('Draft Title');
        expect($retrieved->slug)->toBe('draft-slug');
        expect($retrieved->body)->toBe('Draft body.');
    });

    it('handles select() queries that omit some draft columns', function () {
        // Create a post in draft status
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'My First Post',
            'slug' => 'my-first-post',
            'body' => 'This is the body of my first post.',
            'status' => 'draft',
        ]);

        // When using select() that doesn't include all draft columns,
        // syncAttributesFromDraft() should only sync attributes that were selected
        $partialPost = Post::query()
            ->withoutGlobalScopes()
            ->select(['id', 'title', 'status', 'draft', 'has_been_published'])
            ->find($post->id);

        // The post only has a subset of attributes selected
        // But the draft contains all attributes including 'body', 'slug', etc.
        // These should be skipped since they weren't selected
        expect($partialPost->title)->toBe('My First Post');
    });

    it('handles null draft column gracefully when called directly', function () {
        // Create a published post (no draft)
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Title',
            'slug' => 'slug',
            'body' => 'Body',
            'status' => 'published',
        ]);

        // Manually retrieve without going through the normal flow
        $rawPost = Post::query()
            ->withoutGlobalScopes()
            ->find($post->id);

        // The draft column is null for published posts
        expect($rawPost->draft)->toBeNull();

        // Should not throw - early return when draft is null
        $rawPost->syncAttributesFromDraft();

        // Model remains unchanged
        expect($rawPost->title)->toBe('Title');
    });

    it('handles extra keys from direct DB manipulation gracefully', function () {
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Title',
            'slug' => 'slug',
            'body' => 'Body',
            'status' => 'published',
        ]);

        // Inject a malformed draft directly into the database
        \Illuminate\Support\Facades\DB::table('posts')
            ->where('id', $post->id)
            ->update([
                'status' => 'draft',
                'draft' => json_encode([
                    'title' => 'Draft Title',
                    'slug' => 'draft-slug',
                    'body' => 'Draft body.',
                    'author_id' => 1,
                    'extra_field' => 'This field does not exist in the posts table',
                ]),
            ]);

        // Should not throw - extra_field is skipped
        $retrieved = Post::query()->withoutGlobalScopes()->find($post->id);

        // Valid draft values are synced
        expect($retrieved->title)->toBe('Draft Title');
        expect($retrieved->slug)->toBe('draft-slug');
        expect($retrieved->body)->toBe('Draft body.');
    });
});

describe('syncAttributesFromDraft expected behavior', function () {
    it('only syncs attributes that exist in draft, leaving others with published values', function () {
        // Create a published post
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'body' => 'Original body.',
            'status' => 'published',
        ]);

        // Manually set a draft that's missing some attributes that exist in the model
        // This simulates a scenario where a new column was added to the table after the draft was created
        \Illuminate\Support\Facades\DB::table('posts')
            ->where('id', $post->id)
            ->update([
                'status' => 'draft',
                'draft' => json_encode([
                    'title' => 'Draft Title',
                    // 'slug' is intentionally missing - simulating an old draft
                    // 'body' is intentionally missing
                ]),
            ]);

        // When retrieving, syncAttributesFromDraft() will only sync 'title'
        // because only 'title' exists in the draft
        $retrieved = Post::query()
            ->withoutGlobalScopes()
            ->find($post->id);

        // Draft title is synced
        expect($retrieved->title)->toBe('Draft Title');

        // Published values remain for keys not in draft
        expect($retrieved->slug)->toBe('original-slug');
        expect($retrieved->body)->toBe('Original body.');

        // The model's draft attribute only contains what was stored
        expect($retrieved->draft)->toBe(['title' => 'Draft Title']);
    });

    it('stores published attributes correctly before syncing draft values', function () {
        // Create a post in draft status
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'Published Title',
            'slug' => 'published-slug',
            'body' => 'Published body.',
            'status' => 'draft',
        ]);

        // The publishedAttributes should contain the original values
        $published = $post->getPublishedAttributes();

        expect($published['title'])->toBe('Published Title');
        expect($published['slug'])->toBe('published-slug');
        expect($published['body'])->toBe('Published body.');
    });

    it('maintains published attributes correctly across retrieval', function () {
        // Create a draft post
        $post = Post::factory()->create([
            'author_id' => User::first()->id,
            'title' => 'First Published Title',
            'slug' => 'first-slug',
            'body' => 'First body.',
            'status' => 'draft',
        ]);

        // The current title in draft should be 'First Published Title'
        expect($post->title)->toBe('First Published Title');
        expect($post->getPublishedAttribute('title'))->toBe('First Published Title');

        // Update the draft
        $post->title = 'Draft Title';
        $post->save();

        expect($post->title)->toBe('Draft Title');
        expect($post->getPublishedAttribute('title'))->toBe('First Published Title');

        // Now retrieve from database (triggers syncAttributesFromDraft again)
        $retrieved = Post::query()
            ->withoutGlobalScopes()
            ->find($post->id);

        // The draft title should be loaded
        expect($retrieved->title)->toBe('Draft Title');
        // The published value should be the DB column value
        expect($retrieved->getPublishedAttribute('title'))->toBe('First Published Title');
    });
});
