# Events

Publisher fires numerous events throughout the publishing lifecycle. These allow you to hook into state transitions, validate changes, and perform side effects.

## Publishing Events

### publishing

Fires **before** a model is published. Return `false` to cancel.

```php
static::publishing(function (Post $post) {
    if (!$post->isReadyForPublish()) {
        return false; // Cancel publishing
    }
});
```

### published

Fires **after** a model has been published successfully.

```php
static::published(function (Post $post) {
    Cache::forget("post.{$post->id}");
    event(new PostPublished($post));
});
```

### unpublishing

Fires **before** a model is unpublished. Return `false` to cancel.

```php
static::unpublishing(function (Post $post) {
    if ($post->is_featured) {
        return false; // Prevent unpublishing featured posts
    }
});
```

### unpublished

Fires **after** a model has been unpublished successfully.

```php
static::unpublished(function (Post $post) {
    Cache::forget("post.{$post->id}");
});
```

## Draft Events

### drafting

Fires **before** attributes are saved to the draft column.

```php
static::drafting(function (Post $post) {
    // Modify attributes before drafting
    $post->draft_updated_at = now();
});
```

### drafted

Fires **after** attributes have been saved to the draft column.

```php
static::drafted(function (Post $post) {
    Log::info("Post {$post->id} draft saved");
});
```

### undrafting

Fires **before** draft attributes are moved to regular columns (during publish).

```php
static::undrafting(function (Post $post) {
    // Last chance to modify before publishing
});
```

### undrafted

Fires **after** draft attributes have been moved to regular columns.

```php
static::undrafted(function (Post $post) {
    // Draft has been cleared
});
```

## Revert Events

### reverting

Fires **before** a model is reverted to its published state.

```php
static::reverting(function (Post $post) {
    // About to discard draft changes
});
```

### reverted

Fires **after** a model has been reverted.

```php
static::reverted(function (Post $post) {
    Log::info("Post {$post->id} reverted to published state");
});
```

## Suspension Events

### suspending

Fires **before** a model is marked for deletion.

```php
static::suspending(function (Section $section) {
    // About to be queued for deletion
});
```

### suspended

Fires **after** a model has been marked for deletion.

```php
static::suspended(function (Section $section) {
    Log::info("Section {$section->id} suspended");
});
```

### resuming

Fires **before** a suspension is cleared.

```php
static::resuming(function (Section $section) {
    // About to be restored from suspension
});
```

### resumed

Fires **after** a suspension has been cleared.

```php
static::resumed(function (Section $section) {
    Log::info("Section {$section->id} resumed");
});
```

## Pivot Events

These events fire during publishable relationship operations.

### pivotDraftSyncing / pivotDraftSynced

```php
static::pivotDraftSyncing(function ($post, $relation, $ids, $attributes) {
    // Before sync in draft mode
    // $ids: array of IDs being synced
    // $attributes: array of ID => attributes
});

static::pivotDraftSynced(function ($post, $relation, $changes) {
    // After sync in draft mode
    // $changes contains:
    // [
    //     'draftAttached' => [1, 2],    // IDs of newly attached pivots
    //     'draftUpdated' => [3],         // IDs of updated pivots
    //     'draftDetached' => [4, 5],     // IDs of detached pivots
    // ]
});
```

### pivotDraftAttaching / pivotDraftAttached

```php
static::pivotDraftAttaching(function ($post, $relation, $ids, $attributes) {
    // Before attach in draft mode
});

static::pivotDraftAttached(function ($post, $relation, $ids, $attributes) {
    // After attach in draft mode
});
```

### pivotDraftDetaching / pivotDraftDetached

Fired when pivots are marked for deletion in draft mode. This event fires for **all** pivots regardless of their `has_been_published` status:

```php
static::pivotDraftDetaching(function ($post, $relation, $ids) {
    // Before pivots are marked for deletion (should_delete = true)
    // $ids: array of related model IDs being detached
});

static::pivotDraftDetached(function ($post, $relation, $ids) {
    // After pivots have been marked for deletion
});
```

> **Note:** This event fires consistently whether the pivot has been published or not. The actual deletion happens later during `flush()` (on publish), which fires either `pivotDiscarding`/`pivotDiscarded` for draft-only pivots or `pivotDetaching`/`pivotDetached` for published pivots.

### pivotDraftUpdating / pivotDraftUpdated

```php
static::pivotDraftUpdating(function ($post, $relation, $id, $attributes) {
    // Before pivot attributes updated in draft
});

static::pivotDraftUpdated(function ($post, $relation, $id, $attributes) {
    // After pivot attributes updated in draft
});
```

### pivotReattaching / pivotReattached

Fired when pivots marked for deletion are restored:

```php
static::pivotReattaching(function ($post, $relation, $ids, $attributes) {
    // Before pivots marked for deletion are restored
    // $ids: array of related model IDs being reattached
    // $attributes: array of ID => attributes being applied
});

static::pivotReattached(function ($post, $relation, $ids, $attributes) {
    // After pivots have been restored
});
```

### pivotDiscarding / pivotDiscarded

Fired when draft-only pivots (`has_been_published = false`) are permanently deleted. This typically happens during:
- `flush()` when the parent is published (for pivots with `should_delete = true`)
- `discard()` when reverting the parent
- `revert()` on the parent model

```php
static::pivotDiscarding(function ($post, $relation, $ids) {
    // Before draft-only pivots are permanently deleted
    // $ids: array of related model IDs being discarded
});

static::pivotDiscarded(function ($post, $relation, $ids) {
    // After draft-only pivots have been deleted
});
```

> **Note:** When detaching pivots in draft mode, `pivotDraftDetaching`/`pivotDraftDetached` fires instead (marking `should_delete = true`). The `pivotDiscarding`/`pivotDiscarded` events only fire during the actual permanent deletion.

### pivotAttaching / pivotAttached

Fired when pivots are published (during parent publish). This marks draft pivots as `has_been_published = true`:

```php
static::pivotAttaching(function ($post, $relation, $ids) {
    // Before draft pivots become published
});

static::pivotAttached(function ($post, $relation, $ids) {
    // After draft pivots have been published
});
```

### pivotDetaching / pivotDetached

Fired when published pivots (`has_been_published = true`) are permanently deleted during `flush()`:

```php
static::pivotDetaching(function ($post, $relation, $ids) {
    // Before published pivots are permanently deleted
});

static::pivotDetached(function ($post, $relation, $ids) {
    // After published pivots have been deleted
});
```

## Pivot Event Flow

### On Parent Publish

When a parent model is published, the following pivot events fire in order:

1. **`pivotDiscarding`/`pivotDiscarded`** - Draft-only pivots (`has_been_published = false`) with `should_delete = true` are permanently deleted
2. **`pivotDetaching`/`pivotDetached`** - Published pivots (`has_been_published = true`) with `should_delete = true` are permanently deleted
3. **`pivotAttaching`/`pivotAttached`** - Draft pivots (`has_been_published = false`) without `should_delete` become published

### On Parent Revert

When a parent model is reverted:

1. **`pivotReattaching`/`pivotReattached`** - Pivots with `should_delete = true` are restored
2. **`pivotDiscarding`/`pivotDiscarded`** - Draft-only pivots (`has_been_published = false`) are permanently deleted

## No-Op Behavior

Pivot events only fire when the operation actually changes state. Repeated operations that don't change pivot state are no-ops and fire no events:

| Operation | No-Op Condition |
|-----------|----------------|
| `detach()` in draft | Pivot already has `should_delete = true` |
| `attach()` in draft | Pivot exists with `should_delete = false` |
| `reattach()` | Pivot doesn't have `should_delete = true` |

```php
// First detach - fires pivotDraftDetaching/pivotDraftDetached
$post->tags()->detach($tagId);

// Second detach on same pivot - no events (already marked)
$post->tags()->detach($tagId);
```

## Event Registration

Register event listeners in your model's `booted` method:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected static function booted()
    {
        static::publishing(function (Post $post) {
            // Validation
        });

        static::published(function (Post $post) {
            // Side effects
        });
    }
}
```

Or use an observer:

```php
class PostObserver
{
    public function publishing(Post $post): ?bool
    {
        if (!$post->isComplete()) {
            return false;
        }
        return null;
    }

    public function published(Post $post): void
    {
        // Notify subscribers
    }
}
```

Register the observer:

```php
// In AppServiceProvider
Post::observe(PostObserver::class);
```

## Event Flow

### On Publish

```
publishing → (save) → published
     ↓                    ↓
undrafting → (save) → undrafted
```

### On Unpublish

```
unpublishing → (save) → unpublished
      ↓                     ↓
  drafting → (save) → drafted
```

### On Revert

```
reverting → (refresh/save) → reverted
```

## Cancelling Operations

Events that fire before an operation can return `false` to cancel:

```php
static::publishing(function (Post $post) {
    return false; // Prevents publishing
});

static::unpublishing(function (Post $post) {
    return false; // Prevents unpublishing
});
```

The model's `update()` or `save()` will return `false` if cancelled.

## Suppressing Events

Use the `withoutHandlers` method to temporarily disable event handlers:

```php
static::withoutHandlers(['publishing', 'published'], function () {
    $post->update(['status' => Status::PUBLISHED]);
});
```

## Common Use Cases

### Validation Before Publish

```php
static::publishing(function (Post $post) {
    if (empty($post->title) || empty($post->content)) {
        return false;
    }
});
```

### Cache Invalidation

```php
static::published(function (Post $post) {
    Cache::tags(['posts'])->flush();
});

static::unpublished(function (Post $post) {
    Cache::tags(['posts'])->flush();
});
```

### Notifications

```php
static::published(function (Post $post) {
    Notification::send(
        $post->subscribers,
        new PostPublishedNotification($post)
    );
});
```

### Audit Logging

```php
static::publishing(function (Post $post) {
    AuditLog::create([
        'action' => 'publishing',
        'model_type' => Post::class,
        'model_id' => $post->id,
        'user_id' => auth()->id(),
    ]);
});
```

## Related Documentation

- [Publishing Workflow](publishing-workflow.md) - State transitions
- [Dependent Models](dependent-models.md) - Suspension events
- [Publishable Relationships](publishable-relationships.md) - Pivot events
