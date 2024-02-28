<?php

namespace Plank\Publisher\Tests\Helpers\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends TestModel
{
    /**
     * Get all of the posts that are assigned this tag.
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
}
