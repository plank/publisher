# Custom Pivot Models

Custom pivot models allow you to add behavior, casts, and draft support to pivot table records in publishable many-to-many relationships.

## When to Use Custom Pivots

Use custom pivot models when you need:

- Draft support for pivot attributes (e.g., `position`, `featured`)
- Custom casts on pivot columns
- Pivot-level validation or events
- Complex pivot logic

## Basic Setup

### 1. Create the Pivot Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Plank\Publisher\Concerns\HasPublishablePivotAttributes;

class PostTagPivot extends Pivot
{
    use HasPublishablePivotAttributes;

    protected $table = 'post_tag';

    protected $casts = [
        'featured' => 'boolean',
        'position' => 'integer',
    ];
}
```

### 2. Create the Migration

```php
Schema::create('post_tag', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

    // Your pivot attributes
    $table->boolean('featured')->default(false);
    $table->integer('position')->default(0);

    // Required for publishable pivots
    $table->boolean('has_been_published')->default(false);
    $table->boolean('should_delete')->default(false);

    // Required for pivot attribute drafting
    $table->json('draft')->nullable();

    $table->timestamps();
});
```

### 3. Use in the Relationship

```php
use Plank\Publisher\Relations\PublishableBelongsToMany;

class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['tags'];

    public function tags(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(Tag::class)
            ->using(PostTagPivot::class)
            ->withPivot(['featured', 'position', 'draft'])
            ->withTimestamps();
    }
}
```

## How Pivot Drafting Works

### When Parent is Draft

Changes to pivot attributes are stored in the pivot's `draft` column:

```php
$post->status = Status::DRAFT;
$post->save();

// Update pivot attributes
$post->tags()->updateExistingPivot($tagId, ['position' => 5]);

// Database:
// position: 0 (original published value)
// draft: {"position": 5} (draft value)
```

### When Draft Content is Allowed

Draft values are automatically merged:

```php
Publisher::allowDraftContent();

$pivot = $post->tags()->find($tagId)->pivot;
$pivot->position; // Returns 5 (from draft)
```

### On Publish

Draft values move to regular columns:

```php
$post->update(['status' => Status::PUBLISHED]);

// Database:
// position: 5 (from draft)
// draft: null (cleared)
```

## MorphToMany with Custom Pivot

For polymorphic relationships:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Plank\Publisher\Concerns\HasPublishablePivotAttributes;

class MediablePivot extends MorphPivot
{
    use HasPublishablePivotAttributes;

    protected $table = 'mediables';

    protected $casts = [
        'order' => 'integer',
        'properties' => 'array',
    ];
}
```

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['media'];

    public function media(): PublishableMorphToMany
    {
        return $this->publishableMorphToMany(Media::class, 'mediable')
            ->using(MediablePivot::class)
            ->withPivot(['order', 'properties', 'draft'])
            ->orderBy('order');
    }
}
```

## Pivot Model Methods

The `HasPublishablePivotAttributes` trait provides:

### syncPivotAttributesFromDraft()

Merges draft values into attributes:

```php
$pivot->syncPivotAttributesFromDraft();
```

This is called automatically when loading pivots with draft content allowed.

### shouldLoadPivotFromDraft()

Checks if draft values should be applied:

```php
if ($pivot->shouldLoadPivotFromDraft()) {
    // Draft content is allowed and pivot has draft data
}
```

## Accessing Pivot Attributes

```php
// Standard access
$post->tags->each(function ($tag) {
    $tag->pivot->position;
    $tag->pivot->featured;
});

// Via relationship
$tag = $post->tags()->where('tags.id', $tagId)->first();
$tag->pivot->position;
```

## Updating Pivot Attributes

```php
// Update specific pivot
$post->tags()->updateExistingPivot($tagId, [
    'position' => 3,
    'featured' => true,
]);

// Sync with pivot data
$post->tags()->sync([
    $tag1Id => ['position' => 1],
    $tag2Id => ['position' => 2],
]);
```

## Pivot Validation

Add validation to your pivot model:

```php
class PostTagPivot extends Pivot
{
    use HasPublishablePivotAttributes;

    protected static function booted()
    {
        static::saving(function (PostTagPivot $pivot) {
            if ($pivot->position < 0) {
                throw new \InvalidArgumentException('Position must be positive');
            }
        });
    }
}
```

## Pivot Events

Custom pivot models can use standard Eloquent events:

```php
class PostTagPivot extends Pivot
{
    use HasPublishablePivotAttributes;

    protected static function booted()
    {
        static::created(function (PostTagPivot $pivot) {
            Log::info("Tag attached to post", [
                'post_id' => $pivot->post_id,
                'tag_id' => $pivot->tag_id,
            ]);
        });
    }
}
```

## Self-Referential with Custom Pivot

For models related to themselves:

```php
class RelatedPostPivot extends Pivot
{
    use HasPublishablePivotAttributes;

    protected $table = 'post_post';

    protected $casts = [
        'relationship_type' => 'string',
    ];
}

class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['relatedPosts'];

    public function relatedPosts(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(
            Post::class,
            'post_post',
            'post_id',
            'related_post_id'
        )
            ->using(RelatedPostPivot::class)
            ->withPivot(['relationship_type', 'draft']);
    }
}
```

## Pivot Without Custom Model

If you only need pivot attachment/detachment drafting (not attribute drafting), you don't need a custom pivot model, but the `draft` column is still required on the pivot table:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['tags'];

    public function tags(): PublishableBelongsToMany
    {
        // No custom pivot model needed, but pivot table must have:
        // has_been_published, should_delete, and draft columns
        return $this->publishableBelongsToMany(Tag::class)
            ->withTimestamps();
    }
}
```

The relationship drafts attachments/detachments. Pivot attribute changes will also be drafted if you update them, even without a custom pivot model.

## Testing Pivot Drafts

```php
public function test_pivot_attributes_are_drafted()
{
    $post = Post::factory()->published()->create();
    $tag = Tag::factory()->create();

    $post->tags()->attach($tag, ['position' => 1]);

    // Unpublish and update
    $post->update(['status' => Status::DRAFT]);
    $post->tags()->updateExistingPivot($tag->id, ['position' => 5]);

    // Check database
    $pivot = DB::table('post_tag')
        ->where('post_id', $post->id)
        ->where('tag_id', $tag->id)
        ->first();

    $this->assertEquals(1, $pivot->position); // Published value
    $this->assertEquals(['position' => 5], json_decode($pivot->draft, true));

    // Check with draft content
    Publisher::allowDraftContent();
    $this->assertEquals(5, $post->tags()->find($tag->id)->pivot->position);
}
```

## Related Documentation

- [HasPublishablePivotAttributes](../traits/has-publishable-pivot-attributes.md) - Trait reference
- [Publishable Relationships](../features/publishable-relationships.md) - Relationship overview
- [Events](../features/events.md) - Pivot events
