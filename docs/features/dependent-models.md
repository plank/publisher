# Dependent Models

Dependent models automatically sync their publishing state with a parent model. This creates a cascading publish/unpublish workflow for hierarchical content.

## Overview

When content has a parent-child relationship (e.g., Post → Sections), you often want:
- Child content to publish when the parent publishes
- Child content to unpublish when the parent unpublishes
- Child deletions to be deferred until parent publishes
- Child content to revert when parent reverts

Publisher's dependent model system handles all of this automatically.

## Setting Up Dependencies

### On the Child Model

Define which relationship the child depends on:

```php
class Section extends Model implements Publishable
{
    use IsPublishable;

    // The relationship name that this model depends on
    public string $dependsOnPublishable = 'post';

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
```

### On the Parent Model

Define which relations should sync on publish:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    // Relations to sync when publishing/unpublishing
    protected array $publishingDependents = [
        'sections',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
}
```

## How It Works

### Publishing the Parent

When the parent is published, all dependents sync to published state:

```php
$post = Post::create(['title' => 'My Post']);
$section = $post->sections()->create(['title' => 'Section 1']);

// Both are in draft state
$post->status; // Status::DRAFT
$section->status; // Status::DRAFT

// Publish the parent
$post->update(['status' => Status::PUBLISHED]);

// Child is automatically published
$section->fresh()->status; // Status::PUBLISHED
```

### Unpublishing the Parent

When the parent is unpublished, dependents follow:

```php
$post->update(['status' => Status::DRAFT]);

// Child is automatically unpublished
$section->fresh()->status; // Status::DRAFT
```

### Deleting Dependents

When you delete a dependent while the parent is in draft:

```php
$post->update(['status' => Status::DRAFT]);

// Delete the section
$section->delete();

// Section is NOT deleted - it's suspended
$section->fresh()->isSuspended(); // true
$section->fresh()->should_delete; // true
```

The section is hidden from queries but not actually deleted. When the parent publishes, suspended models are deleted:

```php
$post->update(['status' => Status::PUBLISHED]);

// Now the section is actually deleted
Section::withoutGlobalScopes()->find($section->id); // null
```

### Reverting the Parent

When you revert the parent, dependents are handled:

```php
// Start with a published post with sections
$post->update(['status' => Status::PUBLISHED]);
$section = $post->sections()->create(['title' => 'Section 1']);

// Unpublish and make changes
$post->update(['status' => Status::DRAFT]);
$newSection = $post->sections()->create(['title' => 'New Section']);
$section->delete(); // Suspended, not deleted

// Revert the parent
$post->revert();

// Original section is restored
$section->fresh()->isSuspended(); // false
$section->fresh()->status; // Status::PUBLISHED

// New section (never published) is deleted
Section::withoutGlobalScopes()->find($newSection->id); // null
```

## Nested Dependencies

You can define nested dependent relationships using dot notation:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishingDependents = [
        'sections',           // Direct children
        'comments.replies',   // Nested: comments and their replies
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}

class Comment extends Model implements Publishable
{
    use IsPublishable;

    public string $dependsOnPublishable = 'post';

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class);
    }
}

class Reply extends Model implements Publishable
{
    use IsPublishable;

    public string $dependsOnPublishable = 'comment';

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
```

Publishing the post will cascade through comments and replies.

## Suspension Methods

### suspend()

Marks a model for deletion when parent publishes:

```php
$section->suspend();

$section->should_delete; // true
$section->isSuspended(); // true
```

### resume()

Clears the suspension flag:

```php
$section->resume();

$section->should_delete; // false
$section->isSuspended(); // false
```

### isSuspended()

Check if model is queued for deletion:

```php
if ($section->isSuspended()) {
    // Model will be deleted when parent publishes
}
```

## Suspension Events

| Event | When |
|-------|------|
| `suspending` | Before marking for deletion |
| `suspended` | After marked for deletion |
| `resuming` | Before clearing suspension |
| `resumed` | After suspension cleared |

```php
class Section extends Model implements Publishable
{
    use IsPublishable;

    protected static function booted()
    {
        static::suspended(function (Section $section) {
            Log::info("Section {$section->id} queued for deletion");
        });
    }
}
```

## Query Scopes

Suspended models are hidden by default:

```php
// Normal query - excludes suspended
Section::all(); // Does not include should_delete = true

// Include suspended
Section::withQueuedDeletes()->get();

// Only suspended
Section::onlyQueuedDeletes()->get();

// Explicitly exclude (redundant but explicit)
Section::withoutQueuedDeletes()->get();
```

## The dependsOnPublishableForeignKey

Publisher automatically detects the foreign key from the relationship:

```php
class Section extends Model implements Publishable
{
    use IsPublishable;

    public string $dependsOnPublishable = 'post';

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}

// Publisher knows 'post_id' is the foreign key
// This key is excluded from drafting to maintain relationship integrity
```

## Non-Publishable Parents

If the parent model is not publishable, the dependent features still work but without automatic sync:

```php
class Section extends Model implements Publishable
{
    use IsPublishable;

    public string $dependsOnPublishable = 'category';

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class); // Category is not Publishable
    }
}

// Sections can still be published/unpublished independently
// But there's no automatic sync with Category
```

## Best Practices

1. **Define both sides** - Set `dependsOnPublishable` on child AND `publishingDependents` on parent
2. **Consider deletion timing** - Suspensions are only deleted on parent publish
3. **Handle orphans** - If parent is deleted, handle dependent cleanup manually
4. **Test revert scenarios** - Ensure never-published children are properly removed
5. **Use nested notation carefully** - Deep nesting can cause many queries

## Related Documentation

- [Publishing Workflow](publishing-workflow.md) - Core publishing concepts
- [Events](events.md) - Suspension events
- [Querying](querying.md) - Query scopes for suspended models
