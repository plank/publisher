<?php

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\Section;
use Plank\Publisher\Tests\Helpers\Models\User;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('correctly filters results based on workflow state', function () {
    $draft = Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => '(Published) My First Post',
        'slug' => 'my-first-post',
        'body' => 'A first post.',
        'status' => 'published',
    ]);

    $draft->title = '(Draft) My First Post';
    $draft->status = 'draft';
    $draft->save();

    Post::factory()->create([
        'author_id' => User::first()->id,
        'title' => '(Published) My Second Post',
        'slug' => 'my-second-post',
        'body' => 'A second post.',
        'status' => 'published',
    ]);

    $results = Post::query()
        ->where('title', '(Published) My First Post')
        ->get();

    expect($results)->toBeEmpty();

    $results = Post::query()
        ->where('title', '(Draft) My First Post')
        ->get();

    expect($results)->toHaveCount(1);

    $results = Post::query()
        ->where('title', '(Published) My Second Post')
        ->get();

    expect($results)->toHaveCount(1);

    $results = Post::query()
        ->where('title', 'like', '(Published)%')
        ->get();

    expect($results)->toHaveCount(1);

    $results = Post::query()
        ->where('title', 'like', '(Draft)%')
        ->get();

    expect($results)->toHaveCount(1);
});

describe('only columns of the model table are rewritten to draft lookups', function () {
    it('does not rewrite a column qualified with another table', function () {
        $sql = Post::query()->where('post_post.order', 1)->toSql();

        expect($sql)->toContain('"post_post"."order" = ?')
            ->not->toContain('draft');
    });

    it('rewrites unqualified columns and columns qualified with the model table', function () {
        expect(Post::query()->where('title', 'x')->toSql())->toContain('"posts"."draft"');
        expect(Post::query()->where('posts.title', 'x')->toSql())->toContain('"posts"."draft"');
    });

    it('rewrites columns of a schema qualified model table', function () {
        $model = new class extends Post
        {
            protected $table = 'main.posts';
        };

        expect($model->newQuery()->where('main.posts.title', 'x')->toSql())->toContain('"posts"."draft"');
        expect($model->newQuery()->where('posts.title', 'x')->toSql())->toContain('"posts"."draft"');
        expect($model->newQuery()->where('main.post_post.order', 1)->toSql())->not->toContain('draft');
        expect($model->newQuery()->where('post_post.order', 1)->toSql())->not->toContain('draft');
    });

    it('matches the model table in any letter case', function () {
        expect(Post::query()->where('Posts.title', 'x')->toSql())->toContain('\'$."title"\'');
    });

    it('looks up the bare column name in the draft', function () {
        $schemaQualified = new class extends Post
        {
            protected $table = 'main.posts';
        };

        expect(Post::query()->where('posts.title', 'x')->toSql())->toContain('\'$."title"\'');
        expect(Post::query()->where('posts.title->en', 'x')->toSql())->toContain('\'$."title"."en"\'');
        expect($schemaQualified->newQuery()->where('main.posts.title', 'x')->toSql())->toContain('\'$."title"\'');
        expect($schemaQualified->newQuery()->where('posts.title', 'x')->toSql())->toContain('\'$."title"\'');
    });

    it('treats an unqualified json path containing a dot as a model column', function () {
        $sql = Post::query()->where('title->a.b', 'x')->toSql();

        expect($sql)->toContain('\'$."title"."a.b"\'');
    });

    it('does not rewrite excluded columns qualified with the model table', function () {
        $model = new class extends Post
        {
            protected $table = 'posts';

            protected function excludedFromDraft(): array
            {
                return ['slug'];
            }
        };

        // Excluded by default
        $sql = $model->newQuery()->where('posts.has_been_published', true)->toSql();

        expect($sql)->toContain('"posts"."has_been_published" = ?')
            ->not->toContain('draft');

        // Excluded by the model
        $sql = $model->newQuery()->where('posts.slug', 'x')->toSql();

        expect($sql)->toContain('"posts"."slug" = ?')
            ->not->toContain('draft');
    });

    it('matches exclusions by bare column name, the same way saving does', function () {
        // A qualified entry does not keep "slug" out of the draft when saving,
        // so queries must still look for the drafted slug.
        $model = new class extends Post
        {
            protected $table = 'posts';

            protected function excludedFromDraft(): array
            {
                return ['posts.slug'];
            }
        };

        $id = Post::factory()->create([
            'author_id' => User::first()->id,
            'slug' => 'published-slug',
            'status' => 'published',
        ])->getKey();

        $post = $model->newQuery()->find($id);
        $post->slug = 'draft-slug';
        $post->status = 'draft';
        $post->save();

        expect($post->draft)->toHaveKey('slug', 'draft-slug');
        expect($model->newQuery()->where('slug', 'draft-slug')->pluck('id')->all())->toBe([$id]);
        expect($model->newQuery()->where('posts.slug', 'draft-slug')->pluck('id')->all())->toBe([$id]);
    });
});

it('checks exclusions by bare column name when the column is qualified with the model table', function () {
    $post = new Post;

    expect($post->isExcludedFromDraft('should_delete'))->toBeTrue();
    expect($post->isExcludedFromDraft('posts.should_delete'))->toBeTrue();
    expect($post->isExcludedFromDraft('title'))->toBeFalse();
    expect($post->isExcludedFromDraft('posts.title'))->toBeFalse();
});

it('loads drafted models through a hasManyThrough relation', function () {
    // The relation constrains the intermediate table (posts.author_id), which
    // is not a column of the related model (sections).
    $authorWithSections = new class extends User
    {
        protected $table = 'users';

        public function sections(): HasManyThrough
        {
            return $this->hasManyThrough(Section::class, Post::class, 'author_id', 'post_id');
        }
    };

    $author = User::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id, 'status' => 'published']);
    $section = Section::factory()->create(['post_id' => $post->id, 'status' => 'published']);

    $section->status = 'draft';
    $section->save();

    $lazy = $authorWithSections->newQuery()->find($author->id)->sections;
    $eager = $authorWithSections->newQuery()->with('sections')->find($author->id)->sections;

    expect($lazy->pluck('id')->all())->toBe([$section->id]);
    expect($eager->pluck('id')->all())->toBe([$section->id]);
});
