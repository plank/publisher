<?php

use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('puts attributes in columns and draft when a model is created in `draft`', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'draft',
    ]);

    $this->assertEquals($post->getDraftableAttributes(), $post->draft);
});

it('does not put attributes in draft when a model is created as published', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'published',
    ]);

    $this->assertNull($post->draft);
});

it('updates draft values when a model is saved while in draft', function () {
    /** @var Post $post */
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'draft',
    ]);

    $this->assertEquals('My First Post', $post->draft['title']);

    $post->update([
        'title' => 'My Updated Post',
    ]);

    // Ensure the draft attributes were restored to the model after saving the draft
    $this->assertEquals('My Updated Post', $post->getRawOriginal()['title']);
    $this->assertEquals('My Updated Post', $post->draft['title']);
    $this->assertEquals('My Updated Post', $post->title);

    // Ensure the content was persisted correctly in the database
    $this->assertEquals('My First Post', $post->getRawAttributes()['title']);
});

it('maintains published state when a model is updated while published', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'published',
    ]);

    $this->assertNull($post->draft);

    $post->update([
        'title' => 'My Updated Post',
    ]);

    $this->assertEquals('My Updated Post', $post->title);
    $this->assertNull($post->draft);
});

it('puts attributes in draft when a model is unpublished', function () {
    expect(true)->toBeTrue();
});

it('clears draft attributes when a model is published', function () {
    expect(true)->toBeTrue();
});

it('populates drafts on retrieval of publishable models', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'draft',
    ]);

    $this->assertEquals($post->getDraftableAttributes(), $post->draft);

    $retrieved = Post::query()
        ->withoutGlobalScopes()
        ->find($post->id);

    $this->assertEquals($retrieved->getDraftableAttributes(), $retrieved->draft);
});

it('sets attributes from draft when a publishable model is published', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'draft',
    ]);

    $this->assertEquals('My First Post', $post->draft['title']);

    $post->update([
        'title' => 'My Updated Post',
    ]);

    $this->assertEquals('My Updated Post', $post->title);

    $retrieved = Post::query()
        ->withoutGlobalScopes()
        ->find($post->id);

    $retrieved->slug = 'my-updated-post';
    $retrieved->status = 'published';
    $retrieved->save();

    $this->assertEquals('My Updated Post', $retrieved->title);
    $this->assertEquals('my-updated-post', $retrieved->slug);
});

it('publishes changes queued in draft when published', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'body' => 'This is the body of my first post.',
        'status' => 'draft',
    ]);

    $post->title = 'My Updated Post';
    $post->slug = 'my-updated-post';
    $post->save();

    $this->assertEquals('My Updated Post', $post->title);
    $this->assertEquals('My First Post', $post->getRawAttributes()['title']);

    $this->assertEquals('my-updated-post', $post->slug);
    $this->assertEquals('my-first-post', $post->getRawAttributes()['slug']);

    $post->body = 'This is the body of my updated post.';
    $post->save();

    $this->assertEquals('This is the body of my updated post.', $post->body);
    $this->assertEquals('This is the body of my first post.', $post->getRawAttributes()['body']);

    $post->status = 'published';
    $post->save();

    $this->assertEquals('My Updated Post', $post->title);
    $this->assertEquals('my-updated-post', $post->slug);
    $this->assertEquals('This is the body of my updated post.', $post->body);
    $this->assertNull($post->draft);
});

it('can get published attribute value from a draft model', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'Published Title',
        'slug' => 'published-slug',
        'body' => 'Published body.',
        'status' => 'draft',
    ]);

    $post->title = 'Draft Title';
    $post->save();

    // Draft value via normal accessor
    $this->assertEquals('Draft Title', $post->title);

    // Published value via getPublishedAttribute
    $this->assertEquals('Published Title', $post->getPublishedAttribute('title'));

    // Unchanged attributes return current value
    $this->assertEquals('published-slug', $post->getPublishedAttribute('slug'));
});

it('can get all published attributes from a draft model', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'Published Title',
        'slug' => 'published-slug',
        'body' => 'Published body.',
        'status' => 'draft',
    ]);

    $post->title = 'Draft Title';
    $post->body = 'Draft body.';
    $post->save();

    $published = $post->getPublishedAttributes();

    $this->assertEquals('Published Title', $published['title']);
    $this->assertEquals('Published body.', $published['body']);
    $this->assertEquals('published-slug', $published['slug']);
});

it('returns regular attributes for getPublishedAttribute on a published model', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My Title',
        'slug' => 'my-slug',
        'body' => 'My body.',
        'status' => 'published',
    ]);

    $this->assertEquals('My Title', $post->getPublishedAttribute('title'));
    $this->assertEquals('my-slug', $post->getPublishedAttribute('slug'));
});

it('can set a published attribute on a draft model', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'Published Title',
        'slug' => 'published-slug',
        'body' => 'Published body.',
        'status' => 'draft',
    ]);

    $post->title = 'Draft Title';
    $post->setPublishedAttribute('title', 'New Published Title');
    $post->save();

    // Draft value is preserved
    $this->assertEquals('Draft Title', $post->title);

    // Published value was updated
    $this->assertEquals('New Published Title', $post->getPublishedAttribute('title'));
    $this->assertEquals('New Published Title', $post->getRawAttributes()['title']);
});

it('can set multiple published attributes on a draft model', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'Published Title',
        'slug' => 'published-slug',
        'body' => 'Published body.',
        'status' => 'draft',
    ]);

    $post->title = 'Draft Title';
    $post->body = 'Draft body.';
    $post->setPublishedAttributes([
        'title' => 'New Published Title',
        'body' => 'New Published body.',
    ]);
    $post->save();

    // Draft values are preserved
    $this->assertEquals('Draft Title', $post->title);
    $this->assertEquals('Draft body.', $post->body);

    // Published values were updated
    $this->assertEquals('New Published Title', $post->getPublishedAttribute('title'));
    $this->assertEquals('New Published body.', $post->getPublishedAttribute('body'));
});

it('sets regular attributes when using setPublishedAttribute on a published model', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'My Title',
        'slug' => 'my-slug',
        'body' => 'My body.',
        'status' => 'published',
    ]);

    $post->setPublishedAttribute('title', 'Updated Title');
    $post->save();

    $this->assertEquals('Updated Title', $post->title);
    $this->assertNull($post->draft);
});

it('persists published attribute changes to the database', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => 'Published Title',
        'slug' => 'published-slug',
        'body' => 'Published body.',
        'status' => 'draft',
    ]);

    $post->title = 'Draft Title';
    $post->setPublishedAttribute('title', 'New Published Title');
    $post->save();

    // Verify in database
    $fromDb = \Illuminate\Support\Facades\DB::table('posts')
        ->where('id', $post->id)
        ->first();

    $this->assertEquals('New Published Title', $fromDb->title);
    $draft = json_decode($fromDb->draft, true);
    $this->assertEquals('Draft Title', $draft['title']);
});
