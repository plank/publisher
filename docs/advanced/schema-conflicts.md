# Schema Conflicts

When you rename or drop columns on a publishable model, the draft JSON column may contain outdated keys. Publisher includes automatic schema conflict resolution to handle these situations.

## The Problem

Consider this scenario:

1. You have a `Post` model with a `title` column
2. Some posts have draft data: `{"title": "Draft Title"}`
3. You rename `title` to `headline`
4. The draft JSON still has `{"title": "Draft Title"}` - but `title` no longer exists

When Publisher tries to sync draft attributes, the old key doesn't match any column.

## Automatic Resolution

Publisher listens for schema change events and automatically updates draft JSON when columns are renamed or dropped.

## Configuration

```php
// config/publisher.php
'conflicts' => [
    'queue' => 'sync',  // Queue connection for the job
    'listener' => \Plank\Publisher\Listeners\HandleSchemaConflicts::class,
    'job' => \Plank\Publisher\Jobs\ResolveSchemaConflicts::class,
],
```

### Queue Options

- `'sync'` - Process immediately (default)
- `'default'` - Use the default queue
- `'high'` - Use a specific queue

For large tables, use a queue to avoid blocking migrations:

```php
'conflicts' => [
    'queue' => 'migrations',
],
```

## How It Works

### Column Rename

When you rename a column:

```php
Schema::table('posts', function (Blueprint $table) {
    $table->renameColumn('title', 'headline');
});
```

Publisher's listener:

1. Detects the rename event
2. Dispatches `ResolveSchemaConflicts` job
3. Updates all draft JSON: `{"title": "..."}` → `{"headline": "..."}`

### Column Drop

When you drop a column:

```php
Schema::table('posts', function (Blueprint $table) {
    $table->dropColumn('subtitle');
});
```

Publisher's listener:

1. Detects the drop event
2. Dispatches `ResolveSchemaConflicts` job
3. Removes the key from all draft JSON: `{"subtitle": "..."}` → removed

## The ResolveSchemaConflicts Job

```php
namespace Plank\Publisher\Jobs;

class ResolveSchemaConflicts implements ShouldQueue
{
    public function __construct(
        public string $table,
        public ConflictType $type,
        public string $column,
        public ?string $newColumn = null,
    ) {}

    public function handle(): void
    {
        // Finds all rows with draft data containing the column
        // Updates the JSON to reflect the schema change
    }
}
```

## Conflict Types

```php
namespace Plank\Publisher\Enums;

enum ConflictType: string
{
    case RENAME = 'rename';
    case DROP = 'drop';
}
```

## Manual Resolution

If automatic resolution doesn't run (e.g., you migrated before installing Publisher), resolve manually:

```php
use Plank\Publisher\Jobs\ResolveSchemaConflicts;
use Plank\Publisher\Enums\ConflictType;

// For a rename
ResolveSchemaConflicts::dispatch(
    table: 'posts',
    type: ConflictType::RENAME,
    column: 'title',
    newColumn: 'headline',
);

// For a drop
ResolveSchemaConflicts::dispatch(
    table: 'posts',
    type: ConflictType::DROP,
    column: 'subtitle',
);
```

## Custom Handler

Replace the listener or job with your own:

```php
// config/publisher.php
'conflicts' => [
    'listener' => \App\Listeners\CustomSchemaConflictHandler::class,
    'job' => \App\Jobs\CustomResolveSchemaConflicts::class,
],
```

Example custom listener:

```php
namespace App\Listeners;

use Plank\LaravelSchemaEvents\Events\SchemaColumnRenamed;
use Plank\LaravelSchemaEvents\Events\SchemaColumnDropped;

class CustomSchemaConflictHandler
{
    public function handle(SchemaColumnRenamed|SchemaColumnDropped $event)
    {
        // Custom logic
        // Maybe log the change
        // Maybe notify admins
        // Then dispatch the resolution job
    }
}
```

## Events Listened For

Publisher listens to events from `plank/laravel-schema-events`:

- `SchemaColumnRenamed` - When a column is renamed
- `SchemaColumnDropped` - When a column is dropped

## Testing Schema Changes

When testing migrations that affect publishable models:

```php
public function test_draft_json_updated_on_column_rename()
{
    $post = Post::create([
        'title' => 'Original',
        'status' => Status::DRAFT,
    ]);

    // Draft contains title
    $this->assertArrayHasKey('title', $post->draft);

    // Run migration
    $this->artisan('migrate');

    // Draft should now have headline
    $post->refresh();
    $this->assertArrayHasKey('headline', $post->draft);
    $this->assertArrayNotHasKey('title', $post->draft);
}
```

## Pivot Table Conflicts

If you're using pivot attribute drafting, the same resolution applies to pivot draft columns:

```php
Schema::table('post_tag', function (Blueprint $table) {
    $table->renameColumn('sort_order', 'position');
});

// Pivot draft JSON is also updated
// {"sort_order": 1} → {"position": 1}
```

## Disabling Automatic Resolution

If you want to handle schema conflicts manually:

```php
// config/publisher.php
'conflicts' => [
    'listener' => null, // Disables automatic handling
],
```

## Large Table Considerations

For tables with many rows:

1. **Use a queue** - Avoid blocking the migration
2. **Batch processing** - The job processes in chunks
3. **Monitor progress** - Check queue for completion
4. **Test first** - Run on staging before production

```php
'conflicts' => [
    'queue' => 'schema-updates',
],
```

Then monitor:

```bash
php artisan queue:work schema-updates
```

## Related Documentation

- [Installation](../guides/installation.md) - Initial setup
- [Draft Management](../features/draft-management.md) - How drafts work
- [Plank Ecosystem](plank-ecosystem.md) - Related packages
