# Publishing Workflow

The publishing workflow manages the lifecycle of content from draft to published state and back.

## Workflow States

Publisher uses a `Status` enum with two default states:

```php
use Plank\Publisher\Enums\Status;

Status::DRAFT;     // Content being edited
Status::PUBLISHED; // Content visible to all
```

## Publishing Content

Change the status to `published` to publish content:

```php
use Plank\Publisher\Enums\Status;

// Method 1: Update the status
$post->update(['status' => Status::PUBLISHED]);

// Method 2: Direct assignment
$post->status = Status::PUBLISHED;
$post->save();
```

### What Happens on Publish

1. **Authorization check** - The `publish` gate is evaluated
2. **`publishing` event fires** - Can be cancelled by returning `false`
3. **`undrafting` event fires** - Draft attributes being moved
4. **Draft to columns** - Draft JSON values move to regular columns
5. **`has_been_published` set** - Marked as `true` permanently
6. **`draft` cleared** - Draft column set to `null`
7. **`published` event fires** - Publishing completed
8. **`undrafted` event fires** - Draft sync completed
9. **Pivots published** - Related pivot changes are published
10. **Dependents synced** - Child models are published

## Unpublishing Content

Change the status away from `published`:

```php
// Unpublish to draft
$post->update(['status' => Status::DRAFT]);
```

### What Happens on Unpublish

1. **Authorization check** - The `unpublish` gate is evaluated
2. **`unpublishing` event fires** - Can be cancelled
3. **`drafting` event fires** - Attributes being drafted
4. **Columns to draft** - Current attribute values copied to draft JSON
5. **Original values restored** - Database values restored from original
6. **`unpublished` event fires** - Unpublishing completed
7. **`drafted` event fires** - Draft sync completed
8. **Dependents synced** - Child models are unpublished

## Reverting Content

Revert discards all draft changes and restores the published state:

```php
// Only works if hasEverBeenPublished() is true
$post->revert();
```

### What Happens on Revert

1. **Validation** - Throws `RevertException` if never published
2. **`reverting` event fires**
3. **Database refresh** - Model refreshed from database (published values)
4. **Draft cleared** - Draft column set to `null`
5. **Status restored** - Set back to `published`
6. **`should_delete` cleared** - Suspension flag reset
7. **Pivots reverted** - Pivot changes discarded
8. **Dependents reverted** - Child models reverted or deleted
9. **`reverted` event fires**

```php
try {
    $post->revert();
} catch (\Plank\Publisher\Exceptions\RevertException $e) {
    // Model has never been published
}
```

## State Checking Methods

```php
// Current state
$post->isPublished();      // Currently in published state
$post->isNotPublished();   // Currently in any non-published state

// Transition detection (during save)
$post->isBeingPublished();    // Status changing to published
$post->isBeingUnpublished();  // Status changing from published

// After save
$post->wasPublished();     // Just saved as published
$post->wasUnpublished();   // Just saved as non-published
$post->wasDrafted();       // Attributes were just drafted
$post->wasUndrafted();     // Draft was just cleared

// History
$post->hasEverBeenPublished(); // Has been published at least once
```

## Authorization

Publisher defines gates for workflow control:

```php
use Illuminate\Support\Facades\Gate;

// In your AuthServiceProvider
Gate::define('publish', function ($user, $model) {
    return $user->can('publish-content');
});

Gate::define('unpublish', function ($user, $model) {
    return $user->can('unpublish-content');
});
```

If authorization fails, the save operation returns `false`:

```php
$result = $post->update(['status' => Status::PUBLISHED]);

if (!$result) {
    // Authorization failed or event cancelled
}
```

## Cancelling State Changes

Use events to prevent state transitions:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected static function booted()
    {
        // Prevent publishing incomplete posts
        static::publishing(function (Post $post) {
            if (empty($post->content)) {
                return false; // Cancels the publish
            }
        });

        // Prevent unpublishing featured posts
        static::unpublishing(function (Post $post) {
            if ($post->is_featured) {
                return false;
            }
        });
    }
}
```

## Workflow with Relationships

When publishing a model with publishable relationships:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['tags'];
    protected array $publishingDependents = ['sections'];
}

// Publishing the post will:
// 1. Publish the post itself
// 2. Publish any pending tag attachments
// 3. Delete any tags queued for detachment
// 4. Publish all dependent sections
$post->update(['status' => Status::PUBLISHED]);
```

## Transactional Safety

Publishing and reverting operations use database transactions:

```php
// The revert method wraps everything in a transaction
public function revert(): void
{
    DB::transaction(function () {
        // Revert model
        // Revert relations
        // Revert dependents
    });
}
```

This ensures all-or-nothing behavior - if any part fails, the entire operation rolls back.

## Workflow Diagrams

### Basic Flow

```
CREATE → DRAFT → PUBLISHED → DRAFT → PUBLISHED
                    ↓
               (edit) → back to DRAFT
```

### With Revert

```
DRAFT → PUBLISHED
           ↓
       (unpublish)
           ↓
        DRAFT (with pending changes)
           ↓
       (revert)
           ↓
      PUBLISHED (changes discarded)
```

## Best Practices

1. **Check authorization first** - Use gates appropriately for your use case
2. **Use events for validation** - Prevent invalid state transitions
3. **Handle revert exceptions** - Not all content can be reverted
4. **Consider dependents** - Publishing cascades to related content
5. **Test rollback scenarios** - Ensure failed publishes don't leave partial state

## Related Documentation

- [Events](events.md) - All lifecycle events
- [Authorization](authorization.md) - Gates and permissions
- [Dependent Models](dependent-models.md) - Cascading publish state
- [Publishable Relationships](publishable-relationships.md) - Pivot publishing
