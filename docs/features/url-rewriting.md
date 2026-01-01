# URL Rewriting

Publisher can automatically append a preview key to all generated URLs when draft content is allowed. This ensures users stay in preview mode as they navigate through the application.

## How It Works

When enabled, Publisher replaces Laravel's URL generator with a custom `PublisherUrlGenerator`. This generator:

1. Checks if draft content is currently allowed
2. If so, appends the configured preview key to all URLs
3. Preserves the preview state across navigation

## Configuration

```php
// config/publisher.php
'urls' => [
    'rewrite' => true,       // Enable URL rewriting
    'previewKey' => 'preview', // Query parameter name
],
```

## Example

With URL rewriting enabled and draft content allowed:

```php
Publisher::allowDraftContent();

// These URLs will include the preview key
url('/posts');           // /posts?preview
route('posts.show', 1);  // /posts/1?preview
action([PostController::class, 'index']); // /posts?preview
```

Without draft content:

```php
Publisher::restrictDraftContent();

// URLs are generated normally
url('/posts');           // /posts
route('posts.show', 1);  // /posts/1
```

## Blade Templates

URL rewriting works automatically in Blade:

```blade
{{-- When draft content is allowed, these include ?preview --}}
<a href="{{ url('/posts') }}">Posts</a>
<a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a>

{{-- Forms also include the preview key --}}
<form action="{{ route('posts.update', $post) }}" method="POST">
    {{-- ... --}}
</form>
```

## Asset URLs

Asset URLs are not rewritten:

```php
asset('css/app.css'); // /css/app.css (never includes preview key)
```

## Disabling URL Rewriting

If you prefer to manage preview URLs manually:

```php
// config/publisher.php
'urls' => [
    'rewrite' => false,
],
```

Then manually include the preview key where needed:

```blade
<a href="{{ route('posts.show', ['post' => $post, 'preview' => true]) }}">
    {{ $post->title }}
</a>
```

## Custom Preview Key

Change the query parameter name:

```php
// config/publisher.php
'urls' => [
    'rewrite' => true,
    'previewKey' => 'draft_mode',
],
```

Now URLs use `?draft_mode` instead of `?preview`.

## Excluding URLs from Rewriting

If you need certain URLs to never include the preview key, generate them with draft content temporarily disabled:

```php
$publicUrl = Publisher::withoutDraftContent(function () use ($post) {
    return route('posts.show', $post);
});
```

## How the Generator Works

The `PublisherUrlGenerator` extends Laravel's `UrlGenerator`:

```php
class PublisherUrlGenerator extends UrlGenerator
{
    public function to($path, $extra = [], $secure = null): string
    {
        $url = parent::to($path, $extra, $secure);

        return $this->appendPreviewKey($url);
    }

    protected function appendPreviewKey(string $url): string
    {
        if (!Publisher::draftContentAllowed()) {
            return $url;
        }

        $previewKey = config('publisher.urls.previewKey');
        $separator = str_contains($url, '?') ? '&' : '?';

        return "{$url}{$separator}{$previewKey}";
    }
}
```

## Integration with Admin Panels

When using admin panels like Nova or Filament, URL rewriting ensures preview mode persists:

### Nova

Nova API routes are typically in `draft_paths`, so draft content is already allowed. URL rewriting ensures frontend preview links maintain preview mode.

### Filament

Add Filament paths to `draft_paths`:

```php
'draft_paths' => [
    'admin*',
    'filament*',  // Filament admin routes
    // ...
],
```

## Preview Links for Sharing

Generate shareable preview links:

```php
// Ensure the preview key is included
$previewLink = route('posts.show', [
    'post' => $post,
    config('publisher.urls.previewKey') => true,
]);

// Share with editors
Mail::to($editor)->send(new ReviewPostMail($previewLink));
```

## Testing URL Rewriting

```php
public function test_urls_include_preview_key_when_draft_allowed()
{
    Publisher::allowDraftContent();

    $url = url('/posts');

    $this->assertStringContainsString('preview', $url);
}

public function test_urls_exclude_preview_key_when_draft_restricted()
{
    Publisher::restrictDraftContent();

    $url = url('/posts');

    $this->assertStringNotContainsString('preview', $url);
}
```

## Caveats

1. **JavaScript-generated URLs** - Client-side URL generation won't include the preview key
2. **External links** - Only internal URLs are rewritten
3. **API endpoints** - Consider if preview key should be included in API URLs
4. **Caching** - URLs with preview key create different cache keys

## Related Documentation

- [Middleware](middleware.md) - How preview mode is detected
- [Authorization](authorization.md) - Who can view draft content
- [Admin Panel Integration](../advanced/admin-panel-integration.md) - Panel-specific setup
