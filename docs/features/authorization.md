# Authorization

Publisher uses Laravel's Gate system to control who can publish, unpublish, and view draft content.

## Gates

Publisher defines three gates:

| Gate | Purpose |
|------|---------|
| `publish` | Can the user publish a model? |
| `unpublish` | Can the user unpublish a model? |
| `view-draft-content` | Can the user view draft content? |

## Default Implementation

By default, all gates allow any authenticated user:

```php
Gate::define('publish', function ($user, $model) {
    return $user !== null;
});

Gate::define('unpublish', function ($user, $model) {
    return $user !== null;
});

Gate::define('view-draft-content', function ($user) {
    return $user !== null;
});
```

## Customizing Gates

Override gates in your `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;
use App\Models\Post;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Custom publish gate
        Gate::define('publish', function (User $user, $model) {
            // Only editors and admins can publish
            if (!$user->hasAnyRole(['editor', 'admin'])) {
                return false;
            }

            // Check model-specific permissions
            if ($model instanceof Post) {
                return $user->can('publish-posts');
            }

            return true;
        });

        // Custom unpublish gate
        Gate::define('unpublish', function (User $user, $model) {
            // Same as publish for now
            return Gate::allows('publish', $model);
        });

        // Custom view draft gate
        Gate::define('view-draft-content', function (User $user) {
            return $user->hasAnyRole(['editor', 'admin', 'reviewer']);
        });
    }
}
```

## Model-Specific Authorization

The `publish` and `unpublish` gates receive the model as the second argument:

```php
Gate::define('publish', function (User $user, $model) {
    return match (get_class($model)) {
        Post::class => $user->can('publish-posts'),
        Page::class => $user->can('publish-pages'),
        Product::class => $user->can('publish-products'),
        default => false,
    };
});
```

## Using with Policies

You can delegate to policies:

```php
Gate::define('publish', function (User $user, $model) {
    $policy = Gate::getPolicyFor($model);

    if ($policy && method_exists($policy, 'publish')) {
        return $policy->publish($user, $model);
    }

    return $user->can('publish-content');
});
```

Then in your policy:

```php
class PostPolicy
{
    public function publish(User $user, Post $post): bool
    {
        // Only the author or an admin can publish
        return $user->id === $post->author_id
            || $user->hasRole('admin');
    }
}
```

## Checking Authorization

Publisher checks authorization automatically during state transitions:

```php
// If user can't publish, this returns false
$result = $post->update(['status' => Status::PUBLISHED]);

if (!$result) {
    // Either authorization failed or an event cancelled it
}
```

### Manual Checks

Use the Publisher facade for manual checks:

```php
use Plank\Publisher\Facades\Publisher;

if (Publisher::canPublish($post)) {
    // Show publish button
}

if (Publisher::canUnpublish($post)) {
    // Show unpublish button
}
```

Or use Laravel's Gate directly:

```php
use Illuminate\Support\Facades\Gate;

if (Gate::allows('publish', $post)) {
    // Can publish
}

if (Gate::denies('publish', $post)) {
    // Cannot publish
}
```

## View Draft Content

The `view-draft-content` gate controls:

1. **Middleware behavior** - Whether draft content is enabled
2. **Preview access** - Whether the preview key works

```php
Gate::define('view-draft-content', function (?User $user) {
    // Allow guests with a special token
    if (request()->hasValidSignature()) {
        return true;
    }

    // Otherwise require authentication
    return $user?->can('preview-content') ?? false;
});
```

## Team-Based Authorization

For multi-tenant applications:

```php
Gate::define('publish', function (User $user, $model) {
    // Must belong to the same team
    if (method_exists($model, 'team')) {
        if ($model->team_id !== $user->current_team_id) {
            return false;
        }
    }

    return $user->hasTeamPermission('publish');
});
```

## Workflow-Based Authorization

For approval workflows:

```php
Gate::define('publish', function (User $user, $model) {
    // Check if model requires approval
    if ($model->requires_approval) {
        return $user->hasRole('approver') && $model->is_approved;
    }

    return $user->can('publish-content');
});
```

## Blade Directives

Use standard Laravel authorization in views:

```blade
@can('publish', $post)
    <button type="submit">Publish</button>
@endcan

@can('unpublish', $post)
    <button type="submit">Unpublish</button>
@endcan
```

## API Authorization

In controllers:

```php
public function publish(Post $post)
{
    $this->authorize('publish', $post);

    $post->update(['status' => Status::PUBLISHED]);

    return response()->json(['published' => true]);
}
```

## Failed Authorization Response

When authorization fails during `save()` or `update()`:

```php
$result = $post->update(['status' => Status::PUBLISHED]);

// $result is false - no exception thrown
// The model is NOT saved
```

To throw exceptions instead, check authorization manually:

```php
Gate::authorize('publish', $post);

$post->update(['status' => Status::PUBLISHED]);
```

## Testing Authorization

```php
public function test_only_editors_can_publish()
{
    $editor = User::factory()->editor()->create();
    $viewer = User::factory()->viewer()->create();
    $post = Post::factory()->draft()->create();

    // Editor can publish
    $this->actingAs($editor);
    $this->assertTrue(Gate::allows('publish', $post));

    // Viewer cannot publish
    $this->actingAs($viewer);
    $this->assertFalse(Gate::allows('publish', $post));
}

public function test_unauthorized_publish_returns_false()
{
    $viewer = User::factory()->viewer()->create();
    $post = Post::factory()->draft()->create();

    $this->actingAs($viewer);

    $result = $post->update(['status' => Status::PUBLISHED]);

    $this->assertFalse($result);
    $this->assertTrue($post->fresh()->isNotPublished());
}
```

## Related Documentation

- [Middleware](middleware.md) - Request-level authorization
- [Publishing Workflow](publishing-workflow.md) - When gates are checked
- [Admin Panel Integration](../advanced/admin-panel-integration.md) - Panel-specific auth
