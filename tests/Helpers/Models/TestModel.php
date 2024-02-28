<?php

namespace Plank\Publisher\Tests\Helpers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Plank\Publisher\Contracts\Publishable;

class TestModel extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getRawAttributes()
    {
        $raw = DB::table($this->getTable())
            ->where($this->getKeyName(), $this->getKey())
            ->first();

        return (array) $raw;
    }

    public function getDraftableAttributes()
    {
        if (! $this instanceof Publishable) {
            throw new \Exception('Test Model must implement Publishable to use getDraftableAttributes()');
        }

        $attributes = $this->getRawAttributes();

        foreach (array_keys($attributes) as $key) {
            if ($this->isExcludedFromDraft($key)) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }
}
