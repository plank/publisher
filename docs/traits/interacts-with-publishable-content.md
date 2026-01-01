# InteractsWithPublishableContent Trait

The `InteractsWithPublishableContent` trait overrides Laravel's default `belongsToMany` and `morphToMany` relationship methods to use Publisher's publishable versions. This is useful when you want **all** many-to-many relationships on a model to support draft functionality.

## When to Use

Use this trait when:
- You want all `belongsToMany` relationships to automatically use `PublishableBelongsToMany`
- You want all `morphToMany` relationships to automatically use `PublishableMorphToMany`
- You prefer not to use the explicit `publishableBelongsToMany()` method

## Usage

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Plank\Publisher\Concerns\InteractsWithPublishableContent;
use Plank\Publisher\Concerns\IsPublishable;
use Plank\Publisher\Contracts\Publishable;

class Post extends Model implements Publishable
{
    use IsPublishable;
    use InteractsWithPublishableContent;

    protected array $publishablePivottedRelations = ['tags'];

    // This now automatically uses PublishableBelongsToMany
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
```

## What It Does

The trait overrides two methods from Laravel's `Model` class:

### newBelongsToMany

Replaces the standard `BelongsToMany` relationship with Publisher's `BelongsToMany`:

```php
protected function newBelongsToMany(
    Builder $query,
    Model $parent,
    $table,
    $foreignPivotKey,
    $relatedPivotKey,
    $parentKey,
    $relatedKey,
    $relationName = null,
) {
    return new \Plank\Publisher\Relations\BelongsToMany(
        $query,
        $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName,
    );
}
```

### newMorphToMany

Replaces the standard `MorphToMany` relationship with Publisher's `MorphToMany`:

```php
protected function newMorphToMany(
    Builder $query,
    Model $parent,
    $name,
    $table,
    $foreignPivotKey,
    $relatedPivotKey,
    $parentKey,
    $relatedKey,
    $relationName = null,
    $inverse = false,
) {
    return new \Plank\Publisher\Relations\MorphToMany(
        $query,
        $parent,
        $name,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName,
        $inverse,
    );
}
```

## Comparison

### Without InteractsWithPublishableContent

```php
class Post extends Model implements Publishable
{
    use IsPublishable;

    protected array $publishablePivottedRelations = ['tags', 'categories'];

    // Must use explicit publishable methods
    public function tags(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(Tag::class);
    }

    public function categories(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(Category::class);
    }
}
```

### With InteractsWithPublishableContent

```php
class Post extends Model implements Publishable
{
    use IsPublishable;
    use InteractsWithPublishableContent;

    protected array $publishablePivottedRelations = ['tags', 'categories'];

    // Can use standard Laravel methods
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
```

## Publisher's BelongsToMany vs PublishableBelongsToMany

There's an important distinction:

- **`Plank\Publisher\Relations\BelongsToMany`** - Adds the `HasPublishablePivot` trait to the standard relationship
- **`Plank\Publisher\Relations\PublishableBelongsToMany`** - The complete publishable relationship class

The `InteractsWithPublishableContent` trait uses the former, which provides pivot drafting support while maintaining compatibility with standard Laravel usage.

## Caveats

When using this trait:

1. **All relationships are affected** - Every `belongsToMany` and `morphToMany` on the model will use Publisher's versions
2. **Pivot table requirements** - Your pivot tables must have the required columns (`has_been_published`, `should_delete`, and `draft`)
3. **Remember to register** - You still need to add relationships to `$publishablePivottedRelations` for them to be managed during publishing

## Related Documentation

- [Publishable Relationships](../features/publishable-relationships.md) - How relationships work with Publisher
- [IsPublishable](is-publishable.md) - The main publishable trait
- [HasPublishablePivotAttributes](has-publishable-pivot-attributes.md) - Draft attributes on pivots
