# Plank Ecosystem

Publisher builds on several other packages from the Plank ecosystem. Understanding these dependencies helps you leverage the full power of Publisher and troubleshoot issues.

## Package Dependencies

### plank/before-and-after-model-events

**Purpose**: Provides "before" and "after" variants of Eloquent model events.

**Used by Publisher for**: Firing `publishing`/`published` events at the right time during the save lifecycle.

**Repository**: [github.com/plank/before-and-after-model-events](https://github.com/plank/before-and-after-model-events)

**How it works**:

Standard Eloquent events like `creating` fire during the save process. This package adds:
- `afterEvent('creating', ...)` - Fires after `creating` but before the save completes
- Allows cancellation of the operation from within the "after" handler

```php
// Publisher uses this for timing
static::afterEvent('creating', function ($model) {
    if ($model->isBeingPublished()) {
        $model->firePublishing();
    }
});
```

### plank/laravel-hush

**Purpose**: Temporarily suppresses specific event handlers.

**Used by Publisher for**: Preventing infinite loops and allowing quiet saves during complex operations.

**Repository**: [github.com/plank/laravel-hush](https://github.com/plank/laravel-hush)

**How it works**:

```php
use Plank\LaravelHush\Concerns\HushesHandlers;

class Post extends Model implements Publishable
{
    use IsPublishable;
    use HushesHandlers;
}

// Suppress specific handlers
$post->withoutHandler('deleting', function () {
    $post->delete();
}, [SyncsPublishing::class]);

// Suppress multiple handlers
static::withoutHandlers(['publishing', 'published'], function () {
    $this->saveQuietly();
});
```

### plank/laravel-model-resolver

**Purpose**: Resolves Eloquent models from table names.

**Used by Publisher for**: Finding which model class corresponds to a database table during schema conflict resolution.

**Repository**: [github.com/plank/laravel-model-resolver](https://github.com/plank/laravel-model-resolver)

**How it works**:

When a schema change occurs on a table, Publisher needs to find the corresponding model:

```php
use Plank\LaravelModelResolver\Facades\ModelResolver;

// Given a table name, get the model class
$modelClass = ModelResolver::resolve('posts');
// Returns: App\Models\Post::class
```

### plank/laravel-pivot-events

**Purpose**: Fires events when pivot table records are created, updated, or deleted.

**Used by Publisher for**: Detecting and handling changes to many-to-many relationships.

**Repository**: [github.com/plank/laravel-pivot-events](https://github.com/plank/laravel-pivot-events)

**How it works**:

Standard Eloquent doesn't fire events for pivot operations. This package adds:

```php
// Events fired on pivot changes
$post->tags()->attach($tag); // Fires pivotAttaching, pivotAttached

// Publisher extends these for draft-aware pivots
static::pivotDraftAttached(function ($model, $relation, $ids) {
    // Handle draft pivot attachment
});
```

### plank/laravel-schema-events

**Purpose**: Fires events when database schema changes occur.

**Used by Publisher for**: Detecting column renames and drops to update draft JSON.

**Repository**: [github.com/plank/laravel-schema-events](https://github.com/plank/laravel-schema-events)

**How it works**:

```php
use Plank\LaravelSchemaEvents\Events\SchemaColumnRenamed;
use Plank\LaravelSchemaEvents\Events\SchemaColumnDropped;

// Publisher listens for these events
Event::listen(SchemaColumnRenamed::class, HandleSchemaConflicts::class);
Event::listen(SchemaColumnDropped::class, HandleSchemaConflicts::class);
```

## Dependency Tree

```
plank/publisher
├── plank/before-and-after-model-events
├── plank/laravel-hush
├── plank/laravel-model-resolver
├── plank/laravel-pivot-events
└── plank/laravel-schema-events
```

## Version Compatibility

Publisher's dependencies are versioned to match Laravel versions:

| Publisher | Laravel | Dependencies |
|-----------|---------|--------------|
| ^1.0 | 12.x | ^12.0 of all packages |

Check `composer.json` for exact version constraints:

```json
{
    "require": {
        "php": "^8.2",
        "illuminate/contracts": "^12",
        "plank/before-and-after-model-events": "^12.0",
        "plank/laravel-hush": "^12.1",
        "plank/laravel-model-resolver": "^12.1.2",
        "plank/laravel-pivot-events": "^1.0",
        "plank/laravel-schema-events": "^12.2"
    }
}
```

## Debugging Tips

### Event Not Firing

If publishing events aren't firing:

1. Check `before-and-after-model-events` is registered
2. Verify the model uses `BeforeAndAfterEvents` trait
3. Check for `withoutHandlers` suppressing events

### Pivot Events Not Firing

If pivot events aren't working:

1. Ensure `PivotEventTrait` is used on the model
2. Check relationship is defined correctly
3. Verify pivot table has required columns

### Schema Conflicts Not Resolving

If draft JSON isn't updated on schema changes:

1. Check `laravel-schema-events` listener is registered
2. Verify queue is processing jobs
3. Check `config/publisher.php` conflicts configuration

### Model Not Resolved from Table

If schema conflict resolution fails:

1. Ensure model follows naming conventions
2. Register model manually with `ModelResolver`
3. Check `laravel-model-resolver` configuration

## Using the Packages Independently

You can use these packages without Publisher:

### Before and After Events

```php
use Plank\BeforeAndAfterModelEvents\Concerns\BeforeAndAfterEvents;

class Post extends Model
{
    use BeforeAndAfterEvents;

    protected static function booted()
    {
        static::afterEvent('saving', function ($model) {
            // Runs after 'saving' but before actual save
        });
    }
}
```

### Laravel Hush

```php
use Plank\LaravelHush\Concerns\HushesHandlers;

class Post extends Model
{
    use HushesHandlers;
}

// Temporarily suppress an observer method
$post->withoutHandler('saving', function () {
    $post->save();
});
```

### Laravel Pivot Events

```php
use Plank\LaravelPivotEvents\Traits\PivotEventTrait;

class Post extends Model
{
    use PivotEventTrait;

    protected static function booted()
    {
        static::pivotAttached(function ($model, $relation, $ids) {
            Log::info("Attached to {$relation}: " . implode(', ', $ids));
        });
    }
}
```

## Contributing

Each package has its own repository. When contributing to Publisher:

1. Identify which package the issue belongs to
2. Open issues/PRs on the appropriate repository
3. Reference related issues across repos

## Related Documentation

- [Schema Conflicts](schema-conflicts.md) - Using schema events
- [Publishable Relationships](../features/publishable-relationships.md) - Using pivot events
- [Events](../features/events.md) - Using before/after events
