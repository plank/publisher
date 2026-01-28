# Draft Management

Draft management is the core feature of Publisher that allows you to work on content changes without affecting the published version.

## How Drafts Work

When a publishable model is in a non-published state, attribute changes are automatically stored in the `draft` JSON column instead of overwriting the regular attribute columns.

### Creating a Draft

```php
use App\Models\Post;
use Plank\Publisher\Enums\Status;

// New models start as drafts by default
$post = Post::create([
    'title' => 'My New Post',
    'content' => 'This is the content.',
]);

// The model is in draft state
$post->status; // Status::DRAFT

// Attributes are stored in the draft column
// Database: draft = {"title": "My New Post", "content": "This is the content."}
```

### Updating a Draft

```php
// Update the draft
$post->update([
    'title' => 'Updated Title',
]);

// Changes go to the draft column
// Database: draft = {"title": "Updated Title", "content": "This is the content."}
```

### Publishing

```php
// Publish the post
$post->update(['status' => Status::PUBLISHED]);

// Draft values move to regular columns
// Database: title = "Updated Title", content = "This is the content.", draft = null
```

### Editing Published Content

```php
// Unpublish to make changes
$post->update(['title' => 'New Title', 'status' => Status::DRAFT]);

// Published values stay in columns, changes go to draft
// Database: title = "Updated Title" (published), draft = {"title": "New Title"}
```

## Reading Draft Values

### With Draft Content Allowed

When draft content is allowed (e.g., in admin contexts), the model automatically merges draft values:

```php
use Plank\Publisher\Facades\Publisher;

Publisher::allowDraftContent();

$post = Post::find(1);
$post->title; // Returns the draft value if in draft mode
```

### With Draft Content Restricted

When draft content is restricted (e.g., public pages), you see published values:

```php
Publisher::restrictDraftContent();

$post = Post::find(1);
$post->title; // Returns the published value only
```

### Temporary Context Switching

```php
// Execute with draft content
$draftPosts = Publisher::withDraftContent(function () {
    return Post::all();
});

// Execute without draft content
$publishedPosts = Publisher::withoutDraftContent(function () {
    return Post::all();
});
```

## Attribute Synchronization

### syncAttributesFromDraft

Called automatically when loading models with draft content allowed:

```php
// Merges draft JSON into model attributes
$post->syncAttributesFromDraft();
```

### putAttributesInDraft

Called automatically when saving in draft state:

```php
// Copies current attributes to draft column
$post->putAttributesInDraft();
```

### publishAttributes

Called automatically when publishing:

```php
// Moves draft values to regular columns
$post->publishAttributes();
```

## Excluded Attributes

Not all columns should be drafted. Publisher automatically excludes:

| Excluded | Reason |
|----------|--------|
| Primary key | Cannot change identity |
| `created_at`, `updated_at` | Timestamps are automatic |
| `draft`, `status`, `has_been_published`, `should_delete` | Publisher columns |
| `dependsOnPublishable` foreign key | Relationship integrity |
| Soft delete column | Deletion is separate concern |
| Aggregate columns | Computed values |

### Custom Exclusions

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    public function isExcludedFromDraft(string $key): bool
    {
        // Exclude additional columns
        if (in_array($key, ['view_count', 'cached_data'])) {
            return true;
        }

        return parent::isExcludedFromDraft($key);
    }
}
```

## Raw Draft Access

To access the raw draft JSON without merging:

```php
// Get raw draft data
$draft = $post->getOriginal('draft');
$draft = $post->getRawOriginal('draft');
```

## Accessing Published Attributes

When working with a draft model (with `Publisher::withDraftContent()` enabled), you may need to read or modify the published values independently from the draft values. Publisher provides methods for this purpose.

### Reading Published Values

```php
Publisher::withDraftContent(function () {
    $post = Post::find(1);

    // Normal attribute access returns draft value
    $post->title; // "Draft Title"

    // Get a specific published attribute
    $post->getPublishedAttribute('title'); // "Published Title"

    // Get all published attributes
    $published = $post->getPublishedAttributes();
    // ['title' => 'Published Title', 'content' => 'Published content...', ...]
});
```

### Modifying Published Values

You can modify published values while preserving draft values:

```php
Publisher::withDraftContent(function () {
    $post = Post::find(1);

    // Modify the draft value normally
    $post->title = 'New Draft Title';

    // Modify the published value separately
    $post->setPublishedAttribute('title', 'New Published Title');

    // Or set multiple published attributes at once
    $post->setPublishedAttributes([
        'title' => 'New Published Title',
        'slug' => 'new-published-slug',
    ]);

    $post->save();

    // After save:
    // - Draft column contains: {"title": "New Draft Title", ...}
    // - Published column contains: "New Published Title"
});
```

### Behavior on Published Models

When called on a published model (not in draft state), these methods simply access and modify the regular attributes:

```php
$post = Post::find(1); // Published model

$post->getPublishedAttribute('title'); // Same as $post->title
$post->setPublishedAttribute('title', 'New Title'); // Same as $post->title = 'New Title'
```

### Use Cases

These methods are useful when you need to:

- Update metadata on both published and draft versions simultaneously
- Sync certain attributes (like `version_id`) across both states
- Read the "live" published value while editing a draft

```php
// Example: Update version_id on both published and draft
if ($model->isNotPublished()) {
    $model->setPublishedAttribute('version_id', $newVersion->id);
}
$model->version_id = $newVersion->id;
$model->save();
```

## Draft Events

The following events fire during draft operations:

| Event | When |
|-------|------|
| `drafting` | Before saving attributes to draft column |
| `drafted` | After attributes saved to draft column |
| `undrafting` | Before moving draft to regular columns |
| `undrafted` | After draft moved to regular columns |

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected static function booted()
    {
        static::drafting(function (Post $post) {
            // Validate or modify before drafting
        });

        static::drafted(function (Post $post) {
            // Log or notify after drafted
        });
    }
}
```

## Checking Draft State

```php
// Is the model currently being drafted?
$post->shouldBeDrafted();

// Should attributes be loaded from draft?
$post->shouldLoadFromDraft();

// Was the model just drafted in this save?
$post->wasDrafted();

// Was the model just undrafted (published)?
$post->wasUndrafted();
```

## Database Structure

The draft column stores JSON with attribute key-value pairs:

```json
{
    "title": "Draft Title",
    "content": "Draft content here...",
    "meta_description": "Draft meta description"
}
```

This structure allows:
- Partial updates (only changed attributes are stored)
- Efficient querying with JSON path syntax
- Easy comparison with published values

## Best Practices

1. **Don't access draft column directly** - Use model attributes; Publisher handles merging
2. **Use Publisher facade for context** - Don't manually query the draft column
3. **Consider what to exclude** - Cached data, computed values, and external IDs often shouldn't be drafted
4. **Test both contexts** - Ensure your code works with both draft allowed and restricted

## Related Documentation

- [Core Concepts](../guides/core-concepts.md) - Fundamental concepts
- [Publishing Workflow](publishing-workflow.md) - Publish, unpublish, and revert
- [Events](events.md) - All lifecycle events
