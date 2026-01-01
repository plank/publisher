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
});

static::pivotDraftSynced(function ($post, $relation, $changes) {
    // After sync in draft mode
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

```php
static::pivotDraftDetaching(function ($post, $relation, $ids) {
    // Before detach in draft mode (records will be suspended)
});

static::pivotDraftDetached(function ($post, $relation, $ids) {
    // After detach in draft mode
});
```

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

```php
static::pivotReattaching(function ($post, $relation) {
    // Before suspended pivots are restored
});

static::pivotReattached(function ($post, $relation) {
    // After suspended pivots restored
});
```

### pivotDiscarding / pivotDiscarded

```php
static::pivotDiscarding(function ($post, $relation) {
    // Before unpublished pivot attachments are removed
});

static::pivotDiscarded(function ($post, $relation) {
    // After unpublished pivot attachments removed
});
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
