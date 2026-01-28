# IsPublishable Trait

The `IsPublishable` trait is the core trait that transforms any Eloquent model into a publishable model with full draft and publishing workflow support.

## Usage

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Plank\Publisher\Concerns\IsPublishable;
use Plank\Publisher\Contracts\Publishable;

class Post extends Model implements Publishable
{
    use IsPublishable;
}
```

## Required Database Columns

The trait expects the following columns on your model's table:

| Column | Type | Default Name | Description |
|--------|------|--------------|-------------|
| Draft | `json` | `draft` | Stores draft attribute values |
| Workflow | `string` | `status` | Current workflow state |
| Has Been Published | `boolean` | `has_been_published` | Ever been published |
| Should Delete | `boolean` | `should_delete` | Queued for deletion |

## What the Trait Provides

### State Management

```php
// Check states
$post->isPublished();
$post->isNotPublished();
$post->isBeingPublished();
$post->isBeingUnpublished();
$post->hasEverBeenPublished();

// Check dirty states
$post->wasPublished();
$post->wasUnpublished();
$post->wasDrafted();
$post->wasUndrafted();
```

### Published Attribute Access

Access and modify published values independently from draft values:

```php
// Get published values (works on both draft and published models)
$post->getPublishedAttribute('title');
$post->getPublishedAttributes();

// Set published values (useful for updating both states)
$post->setPublishedAttribute('version_id', $newVersionId);
$post->setPublishedAttributes(['version_id' => $newVersionId, 'synced_at' => now()]);
```

See [Draft Management](../features/draft-management.md#accessing-published-attributes) for detailed usage.

### Reverting Changes

```php
// Revert to the last published state
// Only works if hasEverBeenPublished() is true
$post->revert();
```

### Suspension (Queued Deletion)

```php
// Mark for deletion when parent publishes
$post->suspend();

// Clear the deletion queue
$post->resume();

// Check suspension status
$post->isSuspended();
```

### Custom Query Builder

The trait provides a custom `PublisherBuilder` with additional query methods:

```php
// Only published models
Post::onlyPublished()->get();

// Only draft models
Post::onlyDraft()->get();

// Include suspended models
Post::withQueuedDeletes()->get();

// Only suspended models
Post::onlyQueuedDeletes()->get();
```

### Publishing Dependents

Define child models that should sync their publishing state:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishingDependents = [
        'sections',           // Direct relation
        'comments.replies',   // Nested relation
    ];
}
```

### Publishable Pivots

Define many-to-many relationships with draft support:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = [
        'featured',
        'media',
    ];

    public function featured(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(Post::class, 'post_post');
    }

    public function media(): PublishableMorphToMany
    {
        return $this->publishableMorphToMany(Media::class, 'mediable');
    }
}
```

### Depends On Publishable

For child models that should sync their state from a parent:

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
```

## Column Name Configuration

If your columns use different names, configure them in `config/publisher.php`:

```php
'columns' => [
    'draft' => 'draft_data',
    'workflow' => 'publication_status',
    'has_been_published' => 'is_published',
    'should_delete' => 'pending_delete',
],
```

Or override the methods on your model:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    public function draftColumn(): string
    {
        return 'draft_data';
    }

    public function workflowColumn(): string
    {
        return 'publication_status';
    }

    public function hasBeenPublishedColumn(): string
    {
        return 'is_published';
    }

    public function shouldDeleteColumn(): string
    {
        return 'pending_delete';
    }
}
```

## Excluding Attributes from Draft

Certain columns are automatically excluded from draft storage:

- Primary key
- Timestamps (`created_at`, `updated_at`)
- Publisher columns (`draft`, `status`, `has_been_published`, `should_delete`)
- Foreign keys for `dependsOnPublishable`
- Soft delete column (if using `SoftDeletes`)
- Aggregate columns (counts, sums) when `ignore.counts` is true

To exclude additional columns:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    public function isExcludedFromDraft(string $key): bool
    {
        return in_array($key, ['cached_views', 'last_crawled_at'])
            || parent::isExcludedFromDraft($key);
    }
}
```

## Global Scope

The trait automatically adds `PublisherScope` which:

- Hides draft models when draft content is restricted
- Hides suspended models (queued for deletion)
- Can be bypassed when needed:

```php
use Plank\Publisher\Scopes\PublisherScope;

Post::withoutGlobalScope(PublisherScope::class)->get();
```

## Events

The trait fires numerous events during the publishing lifecycle. See [Events](../features/events.md) for details.

## Related Traits

The `IsPublishable` trait combines functionality from:

- `BeforeAndAfterEvents` - Before/after event system
- `FiresPublishingEvents` - Publishing lifecycle events
- `HasPublishableAttributes` - Draft attribute management
- `HasPublishableRelationships` - Pivot relationship support
- `SyncsPublishing` - Dependent model synchronization
