<?php

namespace Plank\Publisher\Tests\Helpers\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Plank\Publisher\Concerns\InteractsWithPublishableContent;

class Tag extends TestModel
{
    use InteractsWithPublishableContent;

    /**
     * Get all of the posts that are assigned this tag.
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
}
