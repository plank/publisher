# HasPublishablePivotAttributes Trait

The `HasPublishablePivotAttributes` trait enables draft support for attributes on custom pivot models. This is used when you need to draft changes to the extra columns on a pivot table.

## When to Use

Use this trait when:
- You have a `belongsToMany` or `morphToMany` relationship with additional pivot columns
- You want changes to pivot attributes to be drafted alongside the parent model
- You're using a custom pivot model class

## Usage

### 1. Create a Custom Pivot Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Plank\Publisher\Concerns\HasPublishablePivotAttributes;

class PostMediaPivot extends Pivot
{
    use HasPublishablePivotAttributes;

    protected $table = 'mediables';
}
```

### 2. Add the Draft Column to Your Pivot Table

Create a migration to add the `draft` column:

```php
Schema::table('mediables', function (Blueprint $table) {
    $table->json('draft')->nullable();
});
```

### 3. Use the Custom Pivot in Your Relationship

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['media'];

    public function media(): PublishableMorphToMany
    {
        return $this->publishableMorphToMany(Media::class, 'mediable')
            ->using(PostMediaPivot::class)
            ->withPivot(['position', 'caption', 'draft']);
    }
}
```

## How It Works

When draft content is allowed and you access pivot attributes:

1. The trait checks if the pivot should load from draft
2. Draft JSON values are merged over the regular pivot attributes
3. You see the current working values, not the published ones

### Example

```php
// Attach media with a caption
$post->media()->attach($mediaId, ['caption' => 'Original caption']);

// Publish the post
$post->update(['status' => Status::PUBLISHED]);

// Later, update the caption while in draft mode
$post->update(['status' => Status::DRAFT]);
$post->media()->updateExistingPivot($mediaId, ['caption' => 'Updated caption']);

// The pivot table now contains:
// caption: 'Original caption' (published value)
// draft: {"caption": "Updated caption"} (draft value)

// When viewing with draft content:
Publisher::withDraftContent(function () use ($post, $mediaId) {
    $media = $post->media()->find($mediaId);
    $media->pivot->caption; // Returns "Updated caption"
});

// When viewing published content:
Publisher::withoutDraftContent(function () use ($post, $mediaId) {
    $media = $post->media()->find($mediaId);
    $media->pivot->caption; // Returns "Original caption"
});
```

## Configuration

The trait uses the same `draft` column name from your Publisher configuration:

```php
'columns' => [
    'draft' => 'draft', // Used for both models and pivots
],
```

## Key Methods

### syncPivotAttributesFromDraft

Merges draft values into the pivot's attributes:

```php
// This is called automatically when loading pivots
$pivot->syncPivotAttributesFromDraft();
```

### shouldLoadPivotFromDraft

Determines if draft values should be loaded:

```php
if ($pivot->shouldLoadPivotFromDraft()) {
    // Draft attributes will be applied
}
```

## Publishing Pivot Attributes

When the parent model is published:

1. `publishPivotAttributes()` is called on all publishable pivot relationships
2. Draft JSON values are moved to their regular columns
3. The `draft` column is cleared

When the parent model is reverted:

1. `revertPivotAttributes()` is called
2. The `draft` column is cleared
3. Pivots return to their published state

## Related Documentation

- [Custom Pivot Models](../advanced/custom-pivot-models.md) - Advanced pivot configurations
- [Publishable Relationships](../features/publishable-relationships.md) - BelongsToMany and MorphToMany
- [IsPublishable](is-publishable.md) - The main publishable trait
