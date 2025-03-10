<?php

namespace Plank\Publisher\Tests\Helpers\Models;

use Plank\Publisher\Relations\PublishableMorphToMany;

class Media extends TestModel
{
    public function posts(): PublishableMorphToMany
    {
        return $this->publishableMorphToMany(Post::class, 'mediable');
    }
}
