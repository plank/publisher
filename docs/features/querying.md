# Querying

Publisher provides query scopes and draft-aware WHERE clause handling for querying publishable models.

## Global Scope

The `PublisherScope` is automatically applied to all publishable models:

### When Draft Content is Restricted

Only published models are visible:

```php
Publisher::restrictDraftContent();

Post::all(); // Only status = 'published'
```

### When Draft Content is Allowed

All models except suspended ones are visible:

```php
Publisher::allowDraftContent();

Post::all(); // All except should_delete = true
```

### Bypassing the Global Scope

```php
use Plank\Publisher\Scopes\PublisherScope;

// Get all models regardless of status
Post::withoutGlobalScope(PublisherScope::class)->get();
```

## Query Scopes

### onlyPublished()

Only return published models:

```php
Post::onlyPublished()->get();

// SQL: WHERE status = 'published'
```

### onlyDraft()

Only return draft models:

```php
Post::onlyDraft()->get();

// SQL: WHERE status != 'published'
```

### withQueuedDeletes()

Include suspended models (normally hidden):

```php
Post::withQueuedDeletes()->get();

// Includes models with should_delete = true
```

### withoutQueuedDeletes()

Explicitly exclude suspended models (default behavior, but can be useful for clarity):

```php
Post::withoutQueuedDeletes()->get();

// Excludes models with should_delete = true
```

### onlyQueuedDeletes()

Only return suspended models:

```php
Post::onlyQueuedDeletes()->get();

// SQL: WHERE should_delete = true
```

## Draft-Aware WHERE Clauses

When draft content is allowed, WHERE clauses on draftable columns automatically check both the published column value and the draft JSON:

### How It Works

```php
Publisher::allowDraftContent();

Post::where('title', 'Hello')->get();
```

This generates SQL like:

```sql
SELECT * FROM posts WHERE (
    (status = 'published' AND title = 'Hello')
    OR
    (status != 'published' AND draft->>'title' = 'Hello')
)
```

This ensures queries work correctly whether viewing the published value or draft value.

### Columns That Get Draft-Aware Treatment

- All model attributes that are not excluded from drafting
- Does NOT apply to: primary key, timestamps, publisher columns, foreign keys

### When It Applies

Draft-aware WHERE clauses only apply when:

1. `Publisher::draftContentAllowed()` is `true`
2. The column is not excluded from drafting
3. The query is using the `PublisherBuilder`

## Combining Scopes

```php
// Published posts with a specific tag
Post::onlyPublished()
    ->whereHas('tags', fn($q) => $q->where('name', 'Laravel'))
    ->get();

// All drafts including suspended
Post::onlyDraft()
    ->withQueuedDeletes()
    ->get();
```

## Relationship Queries

When querying relationships on publishable models:

```php
$post = Post::find(1);

// The relationship query respects draft content visibility
$post->sections()->get();

// If you need to bypass:
$post->sections()->withoutGlobalScope(PublisherScope::class)->get();
```

## Aggregate Queries

Aggregate queries also respect the global scope:

```php
Publisher::restrictDraftContent();

Post::count(); // Only counts published
Post::max('views'); // Only from published

Publisher::allowDraftContent();

Post::count(); // Counts all non-suspended
```

## Pagination

Pagination works with the scopes:

```php
Post::onlyPublished()->paginate(10);

Post::onlyDraft()->paginate(10);
```

## Eager Loading

Eager loaded relationships also respect visibility:

```php
// Loads only published posts with their published sections
Publisher::restrictDraftContent();
Post::with('sections')->get();

// Loads all non-suspended posts with their sections
Publisher::allowDraftContent();
Post::with('sections')->get();
```

## Context Switching in Queries

```php
// Query with specific visibility
$published = Publisher::withoutDraftContent(function () {
    return Post::where('featured', true)->get();
});

$all = Publisher::withDraftContent(function () {
    return Post::where('featured', true)->get();
});
```

## Raw Query Access

If you need to bypass Publisher's query modifications entirely:

```php
use Illuminate\Support\Facades\DB;

// Direct database query
$posts = DB::table('posts')
    ->where('status', 'published')
    ->get();
```

## JSON Column Queries

You can query the draft column directly if needed:

```php
// Find posts with specific draft value
Post::whereJsonContains('draft->tags', 'important')->get();

// Check if draft exists
Post::whereNotNull('draft')->get();
```

## Performance Considerations

1. **Draft-aware WHERE is more complex** - Adds OR conditions, may affect index usage
2. **Use specific scopes when possible** - `onlyPublished()` is simpler than general queries
3. **Index appropriately** - Consider indexes on `status` and `should_delete` columns
4. **Cache published content** - Published content is stable, cache it aggressively

## Query Builder Methods

The `PublisherBuilder` extends Laravel's query builder with:

| Method | Description |
|--------|-------------|
| `onlyPublished()` | Filter to published only |
| `onlyDraft()` | Filter to drafts only |
| `withQueuedDeletes()` | Include suspended |
| `withoutQueuedDeletes()` | Exclude suspended |
| `onlyQueuedDeletes()` | Only suspended |

## Related Documentation

- [Draft Management](draft-management.md) - How drafts work
- [Middleware](middleware.md) - Controlling draft visibility per request
- [IsPublishable](../traits/is-publishable.md) - Trait reference
