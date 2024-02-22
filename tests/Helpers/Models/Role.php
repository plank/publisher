<?php

namespace Plank\Publisher\Tests\Helpers\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends TestModel
{
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
