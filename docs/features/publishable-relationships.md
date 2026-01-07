# Publishable Relationships

Publisher extends Laravel's `belongsToMany` and `morphToMany` relationships to support draft states for pivot table records.

## Overview

Standard Laravel relationships don't consider publishing states. When you attach or detach related models, the changes are immediate. Publishable relationships allow these changes to be drafted and only applied when the parent model is published.

## Defining Publishable Relationships

### BelongsToMany

```php
use Plank\Publisher\Relations\PublishableBelongsToMany;

class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['tags'];

    public function tags(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(Tag::class);
    }
}
```

### MorphToMany

```php
use Plank\Publisher\Relations\PublishableMorphToMany;

class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['media'];

    public function media(): PublishableMorphToMany
    {
        return $this->publishableMorphToMany(Media::class, 'mediable');
    }
}
```

## Pivot Table Requirements

Publishable pivot tables need additional columns:

```php
Schema::create('post_tag', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

    // Required for publishable pivots
    $table->boolean('has_been_published')->default(false);
    $table->boolean('should_delete')->default(false);
    $table->json('draft')->nullable();

    $table->timestamps();
});
```

## Registering Publishable Relations

You **must** register publishable relationships in the `$publishablePivottedRelations` property:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = [
        'tags',
        'categories',
        'media',
    ];
}
```

This tells Publisher to manage these relationships during publish/revert operations.

## How It Works

### When Parent is Published

Attaching and detaching are immediate:

```php
$post->status = Status::PUBLISHED;
$post->save();

// Attachment is immediate
$post->tags()->attach($tag);
// Pivot: has_been_published = true, should_delete = false

// Detachment is immediate
$post->tags()->detach($tag);
// Pivot record deleted immediately
```

### When Parent is Draft

Changes are queued for the next publish:

```php
$post->status = Status::DRAFT;
$post->save();

// New attachment is drafted
$post->tags()->attach($newTag);
// Pivot: has_been_published = false, should_delete = false
// Fires: pivotDraftAttaching, pivotDraftAttached

// Detaching marks pivot for deletion (consistent for all pivot types)
$post->tags()->detach($tag);
// Fires: pivotDraftDetaching, pivotDraftDetached
// Pivot: should_delete = true (deleted on publish)
```

> **Note:** Detaching in draft mode behaves consistently regardless of whether the pivot has been published. All pivots are marked with `should_delete = true` and the actual deletion happens during publish. The events fired during publish differ based on `has_been_published` status (see "On Publish" below).

### Reattaching Detached Pivots

When you attach an ID that has a pivot marked for deletion (`should_delete = true`), the system automatically reattaches it instead of creating a new pivot:

```php
// Tag was previously detached (should_delete = true)
$post->tags()->attach($previouslyDetachedTag, ['position' => 5]);
// Fires: pivotReattaching, pivotReattached (not pivotDraftAttaching)
// Pivot: should_delete = false, position stored in draft
```

### On Publish

When the parent is published, pivot changes are finalized in this order:

1. **Discard draft-only marked pivots** - Pivots with `should_delete = true` AND `has_been_published = false` are permanently deleted
   - Fires: `pivotDiscarding`, `pivotDiscarded`

2. **Detach published marked pivots** - Pivots with `should_delete = true` AND `has_been_published = true` are permanently deleted
   - Fires: `pivotDetaching`, `pivotDetached`

3. **Publish draft pivots** - Pivots with `has_been_published = false` (without `should_delete`) are marked as published
   - Fires: `pivotAttaching`, `pivotAttached`

4. **Publish pivot attributes** - Draft attribute values are moved to their regular columns

```php
// Publishing the post finalizes all pivot changes
$post->update(['status' => Status::PUBLISHED]);
```

### On Revert

When the parent is reverted, pivot changes are rolled back in this order:

1. **Restore marked pivots** - Pivots with `should_delete = true` have the flag cleared
   - Fires: `pivotReattaching`, `pivotReattached`

2. **Discard draft-only pivots** - Pivots with `has_been_published = false` are permanently deleted
   - Fires: `pivotDiscarding`, `pivotDiscarded`

3. **Clear pivot draft attributes** - Draft attribute values are reset to null

```php
// Reverting discards pending pivot changes
$post->revert();
```

## Relationship Methods

### Standard Operations

All standard relationship methods work with draft awareness:

```php
// Attach
$post->tags()->attach($tagId);
$post->tags()->attach([$tag1, $tag2]);
$post->tags()->attach($tagId, ['extra' => 'data']);

// Detach
$post->tags()->detach($tagId);
$post->tags()->detach([$tag1, $tag2]);
$post->tags()->detach(); // Detach all

// Sync
$post->tags()->sync([$tag1, $tag2]);
$post->tags()->sync([$tag1 => ['extra' => 'data']]);

// Toggle
$post->tags()->toggle([$tag1, $tag2]);
```

### Publisher-Specific Methods

```php
// Publish pending pivot changes
$post->tags()->publish();

// Discard unpublished attachments
$post->tags()->discard();

// Restore pivots marked for deletion (should_delete = true)
$post->tags()->reattach();
$post->tags()->reattach([$tag1, $tag2]); // Specific IDs only

// Force delete pivots marked for deletion (should_delete = true)
$post->tags()->forceDetach();
$post->tags()->forceDetach([$tag1, $tag2]); // Specific IDs only

// Delete all queued-for-deletion records
$post->tags()->flush();

// Publish pivot attribute drafts
$post->tags()->publishPivotAttributes();

// Revert pivot attribute drafts
$post->tags()->revertPivotAttributes();
```

## Pivot Events

Publishable relationships fire additional events:

### Draft Operations (while parent is draft)

| Event | When |
|-------|------|
| `pivotDraftSyncing` | Before sync operation in draft |
| `pivotDraftSynced` | After sync operation in draft |
| `pivotDraftAttaching` | Before attach creates new pivot in draft |
| `pivotDraftAttached` | After attach creates new pivot in draft |
| `pivotDraftDetaching` | Before detach marks pivot (`should_delete = true`) |
| `pivotDraftDetached` | After detach marks pivot |
| `pivotDraftUpdating` | Before pivot attributes update in draft |
| `pivotDraftUpdated` | After pivot attributes update in draft |
| `pivotReattaching` | Before restoring marked pivot (`should_delete = false`) |
| `pivotReattached` | After restoring marked pivot |

### Publish/Revert Operations

| Event | When |
|-------|------|
| `pivotAttaching` | Before draft pivot becomes published |
| `pivotAttached` | After draft pivot becomes published |
| `pivotDetaching` | Before published pivot is permanently deleted |
| `pivotDetached` | After published pivot is permanently deleted |
| `pivotDiscarding` | Before draft-only pivot is permanently deleted |
| `pivotDiscarded` | After draft-only pivot is permanently deleted |

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected static function booted()
    {
        static::pivotDraftAttached(function ($post, $relation, $ids) {
            // Log when tags are attached in draft mode
        });
    }
}
```

## Querying Published vs Draft

The global scope handles pivot visibility:

```php
// With draft content restricted: only published attachments visible
Publisher::restrictDraftContent();
$post->tags; // Only tags with has_been_published = true

// With draft content allowed: all non-deleted attachments visible
Publisher::allowDraftContent();
$post->tags; // All tags except should_delete = true
```

## Custom Pivot Models

For pivot attribute drafting, use a custom pivot model:

```php
use Illuminate\Database\Eloquent\Relations\Pivot;
use Plank\Publisher\Concerns\HasPublishablePivotAttributes;

class PostTag extends Pivot
{
    use HasPublishablePivotAttributes;

    protected $table = 'post_tag';
}

class Post extends Model implements Publishable
{
    use IsPublishable;

    public function tags(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(Tag::class)
            ->using(PostTag::class)
            ->withPivot(['position', 'featured', 'draft']);
    }
}
```

See [Custom Pivot Models](../advanced/custom-pivot-models.md) for details.

## Self-Referential Relationships

For models related to themselves:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['relatedPosts'];

    public function relatedPosts(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(Post::class, 'post_post', 'post_id', 'related_post_id');
    }
}
```

## Best Practices

1. **Always register relations** - Add to `$publishablePivottedRelations`
2. **Add required columns** - Include `has_been_published` and `should_delete` on pivot tables
3. **Consider draft column** - Add `draft` if you need pivot attribute drafting
4. **Test both states** - Verify behavior when parent is published vs draft
5. **Handle events** - Use pivot events for side effects

## Related Documentation

- [HasPublishablePivotAttributes](../traits/has-publishable-pivot-attributes.md) - Pivot attribute drafting
- [Custom Pivot Models](../advanced/custom-pivot-models.md) - Advanced configurations
- [Events](events.md) - All lifecycle events
