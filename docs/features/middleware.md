# Middleware

Publisher includes middleware that controls draft content visibility based on the request context.

## Installation

Register the middleware in your application. It should be placed **before** `SubstituteBindings`.

### Laravel 11+

In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(prepend: [
        \Plank\Publisher\Middleware\PublisherMiddleware::class,
    ]);
})
```

Or append to a specific position:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', [
        // ... other middleware
        \Plank\Publisher\Middleware\PublisherMiddleware::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ]);
})
```

### Laravel 10

In `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,

        // Publisher middleware BEFORE SubstituteBindings
        \Plank\Publisher\Middleware\PublisherMiddleware::class,

        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

## What the Middleware Does

The middleware calls `Publisher::shouldEnableDraftContent($request)` to determine if draft content should be visible for the current request.

Draft content is enabled if **any** of these conditions are true:

1. **User has permission** - The `view-draft-content` gate returns true
2. **Path matches draft paths** - The request path matches a pattern in `draft_paths` config
3. **Preview key is present** - The request has the configured preview query parameter

```php
// PublisherMiddleware.php
public function handle(Request $request, Closure $next)
{
    if (Publisher::shouldEnableDraftContent($request)) {
        Publisher::allowDraftContent();
    }

    return $next($request);
}
```

## Configuration

### Draft Paths

Configure paths where draft content should always be enabled:

```php
// config/publisher.php
'draft_paths' => [
    'admin*',       // All admin routes
    'nova-api*',    // Laravel Nova API
    'nova-vendor*', // Laravel Nova assets
    'cms/*',        // Custom CMS routes
],
```

These patterns use Laravel's `Request::is()` method.

### Preview Key

Configure the query parameter used for preview mode:

```php
// config/publisher.php
'urls' => [
    'rewrite' => true,
    'previewKey' => 'preview',
],
```

When `?preview` is present in the URL and the user has `view-draft-content` permission, draft content is enabled.

## Authorization Gate

Define the `view-draft-content` gate in your `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

public function boot()
{
    Gate::define('view-draft-content', function ($user) {
        return $user->hasRole('editor') || $user->hasRole('admin');
    });
}
```

Publisher's default implementation allows any authenticated user:

```php
Gate::define('view-draft-content', function ($user) {
    return $user !== null;
});
```

## Preview Mode

### Generating Preview URLs

When viewing draft content, you may want to share preview links:

```php
// Get a preview URL
$previewUrl = route('posts.show', [
    'post' => $post,
    'preview' => true,
]);
```

### Checking Preview Mode

```php
if (request()->has(config('publisher.urls.previewKey'))) {
    // User is in preview mode
}
```

## Custom Middleware

You can create custom middleware for more complex scenarios:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plank\Publisher\Facades\Publisher;

class AdminDraftContent
{
    public function handle(Request $request, Closure $next)
    {
        // Always enable draft content for authenticated admins
        if ($request->user()?->isAdmin()) {
            Publisher::allowDraftContent();
        }

        return $next($request);
    }
}
```

## Route-Specific Middleware

Apply publisher middleware to specific routes:

```php
// routes/web.php
Route::middleware(['web', PublisherMiddleware::class])->group(function () {
    // Routes that need publisher visibility control
});

// Or always allow draft content on certain routes
Route::middleware(['web', 'admin-draft-content'])->group(function () {
    Route::get('/admin/posts/{post}', [PostController::class, 'edit']);
});
```

## API Routes

For API routes, you may want different behavior:

```php
// routes/api.php
Route::middleware(['api'])->group(function () {
    // Public API - no draft content by default
    Route::get('/posts', [ApiPostController::class, 'index']);
});

Route::middleware(['api', 'auth:sanctum', AdminDraftContent::class])->group(function () {
    // Admin API - draft content for authenticated admins
    Route::get('/admin/posts', [AdminPostController::class, 'index']);
});
```

## Testing

When testing, you may want to control draft visibility:

```php
use Plank\Publisher\Facades\Publisher;

public function test_public_user_sees_only_published()
{
    Publisher::restrictDraftContent();

    $response = $this->get('/posts');

    // Assert only published posts visible
}

public function test_admin_sees_draft_content()
{
    Publisher::allowDraftContent();

    $response = $this->get('/admin/posts');

    // Assert draft posts visible
}
```

Or test the middleware directly:

```php
public function test_preview_key_enables_draft_content()
{
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/posts?preview=true')
        ->assertSee('Draft Post Title');
}
```

## Middleware Order

The middleware order matters:

1. **Authentication** - User must be authenticated first
2. **PublisherMiddleware** - Determines draft visibility
3. **SubstituteBindings** - Route model binding uses correct visibility

If `SubstituteBindings` runs before `PublisherMiddleware`, route model binding won't find draft models.

## Related Documentation

- [URL Rewriting](url-rewriting.md) - Automatic preview URL preservation
- [Authorization](authorization.md) - Gates and permissions
- [Core Concepts](../guides/core-concepts.md) - Draft visibility overview
