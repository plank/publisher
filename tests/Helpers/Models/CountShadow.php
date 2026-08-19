<?php

namespace Plank\Publisher\Tests\Helpers\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Plank\Publisher\Concerns\IsPublishable;
use Plank\Publisher\Contracts\Publishable;

/**
 * Exercises the property-shadow guard in HasPublishableAttributes.
 *
 * `cutSheets` is a camelCase relation, so its withCount alias `cut_sheets_count`
 * slips past `isCount()` (which looks for a method named `cut_sheets`). The public
 * `$cut_sheets_count` property is what marks it as a non-persisted pseudo-attribute.
 */
class CountShadow extends TestModel implements Publishable
{
    use IsPublishable;

    // Declared so Eloquent (and now the draft builder) treats it as non-persisted.
    public ?int $cut_sheets_count = 0;

    public function cutSheets(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
