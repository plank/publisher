<?php

namespace Plank\Publisher\Tests\Helpers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plank\Publisher\Tests\Helpers\Models\Tag;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
