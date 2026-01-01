# Custom Workflow States

While Publisher ships with `published` and `draft` states, you can extend the workflow to include additional states like "pending review" or "scheduled".

## Default Status Enum

Publisher's default enum provides two states:

```php
namespace Plank\Publisher\Enums;

use Plank\Publisher\Contracts\PublishingStatus;

enum Status: string implements PublishingStatus
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public static function published(): self
    {
        return self::PUBLISHED;
    }

    public static function unpublished(): self
    {
        return self::DRAFT;
    }
}
```

## Creating a Custom Status Enum

Create your own enum implementing `PublishingStatus`:

```php
<?php

namespace App\Enums;

use Plank\Publisher\Contracts\PublishingStatus;

enum ContentStatus: string implements PublishingStatus
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED = 'approved';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Return the published state
     */
    public static function published(): self
    {
        return self::PUBLISHED;
    }

    /**
     * Return the default unpublished state
     */
    public static function unpublished(): self
    {
        return self::DRAFT;
    }
}
```

## Configuring the Custom Enum

Update your configuration:

```php
// config/publisher.php
'workflow' => \App\Enums\ContentStatus::class,
```

## Key Concepts

### Published State

The `published()` method defines which state means "live content". Publisher uses this to:

- Determine when to clear the draft column
- Set `has_been_published = true`
- Control visibility with the global scope

### Unpublished State

The `unpublished()` method defines the default draft state. This is used when:

- Creating new models
- Determining when to store attributes in the draft column

### All Non-Published States

Any state other than `published()` is treated as "draft-like":

- Attributes go to the draft column
- Content is hidden from public users
- The model can be reverted

## Workflow Transitions

You can add custom logic for state transitions:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected static function booted()
    {
        static::saving(function (Post $post) {
            $status = $post->status;
            $original = $post->getOriginal('status');

            // Validate transitions
            if ($original === ContentStatus::DRAFT) {
                // Draft can only go to pending_review
                if (!in_array($status, [ContentStatus::DRAFT, ContentStatus::PENDING_REVIEW])) {
                    return false;
                }
            }

            if ($original === ContentStatus::PENDING_REVIEW) {
                // Pending can go to approved or back to draft
                if (!in_array($status, [ContentStatus::PENDING_REVIEW, ContentStatus::APPROVED, ContentStatus::DRAFT])) {
                    return false;
                }
            }
        });
    }
}
```

## State-Specific Behavior

### Query Scopes for Custom States

Add query scopes for your custom states:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    public function scopePendingReview($query)
    {
        return $query->where($this->workflowColumn(), ContentStatus::PENDING_REVIEW);
    }

    public function scopeScheduled($query)
    {
        return $query->where($this->workflowColumn(), ContentStatus::SCHEDULED);
    }

    public function scopeArchived($query)
    {
        return $query->where($this->workflowColumn(), ContentStatus::ARCHIVED);
    }
}
```

### State Checks

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    public function isPendingReview(): bool
    {
        return $this->status === ContentStatus::PENDING_REVIEW;
    }

    public function isScheduled(): bool
    {
        return $this->status === ContentStatus::SCHEDULED;
    }

    public function isArchived(): bool
    {
        return $this->status === ContentStatus::ARCHIVED;
    }
}
```

## Scheduled Publishing

Example implementation for scheduled publishing:

```php
// Add a published_at column to your migration
$table->timestamp('published_at')->nullable();

// In your model
class Post extends Model implements Publishable
{
    use IsPublishable;

    public function schedule(Carbon $publishAt): void
    {
        $this->update([
            'status' => ContentStatus::SCHEDULED,
            'published_at' => $publishAt,
        ]);
    }
}

// Scheduled command to publish
class PublishScheduledContent extends Command
{
    public function handle()
    {
        Post::where('status', ContentStatus::SCHEDULED)
            ->where('published_at', '<=', now())
            ->each(function (Post $post) {
                $post->update(['status' => ContentStatus::PUBLISHED]);
            });
    }
}
```

## Approval Workflow

Example implementation for approval workflow:

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    public function submitForReview(): void
    {
        $this->update(['status' => ContentStatus::PENDING_REVIEW]);

        event(new PostSubmittedForReview($this));
    }

    public function approve(): void
    {
        $this->update(['status' => ContentStatus::APPROVED]);

        event(new PostApproved($this));
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => ContentStatus::DRAFT,
            'rejection_reason' => $reason,
        ]);

        event(new PostRejected($this, $reason));
    }

    public function publish(): void
    {
        if ($this->status !== ContentStatus::APPROVED) {
            throw new \Exception('Post must be approved before publishing');
        }

        $this->update(['status' => ContentStatus::PUBLISHED]);
    }
}
```

## Authorization for Custom States

Extend gates for your custom states:

```php
Gate::define('submit-for-review', function (User $user, $model) {
    return $user->can('edit', $model);
});

Gate::define('approve', function (User $user, $model) {
    return $user->hasRole('editor');
});

Gate::define('publish', function (User $user, $model) {
    // Only admins can publish, and only approved content
    return $user->hasRole('admin')
        && $model->status === ContentStatus::APPROVED;
});
```

## Admin Panel Integration

Display custom states in your admin panel:

```php
// For Nova
Select::make('Status')
    ->options([
        ContentStatus::DRAFT->value => 'Draft',
        ContentStatus::PENDING_REVIEW->value => 'Pending Review',
        ContentStatus::APPROVED->value => 'Approved',
        ContentStatus::SCHEDULED->value => 'Scheduled',
        ContentStatus::PUBLISHED->value => 'Published',
        ContentStatus::ARCHIVED->value => 'Archived',
    ]);

// For Filament
Forms\Components\Select::make('status')
    ->options(ContentStatus::class);
```

## Migration Considerations

When adding new states to an existing application:

```php
// Migration to add new states
public function up()
{
    // If using MySQL, you may need to update the column type
    // or just ensure the string column is long enough
}
```

## Best Practices

1. **Keep published() and unpublished() simple** - These are the anchor states
2. **Document your workflow** - Create a state diagram
3. **Validate transitions** - Prevent invalid state changes
4. **Use events** - Fire events on state transitions
5. **Test all paths** - Ensure every transition works correctly

## Related Documentation

- [Publishing Workflow](../features/publishing-workflow.md) - Core workflow concepts
- [Events](../features/events.md) - Listening to state changes
- [Authorization](../features/authorization.md) - Controlling transitions
