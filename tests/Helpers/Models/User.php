<?php

namespace Plank\Publisher\Tests\Helpers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Plank\Publisher\Concerns\InteractsWithPublishableContent;

class User extends Authenticatable
{
    use HasFactory;
    use InteractsWithPublishableContent;

    protected $guarded = [];

    /**
     * @psalm-return BelongsToMany<Role>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function edited(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
