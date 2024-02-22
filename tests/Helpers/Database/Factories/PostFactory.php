<?php

namespace Plank\Publisher\Tests\Helper\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition()
    {
        $title = $this->faker->words($this->faker->numberBetween(2, 4), true);
        $status = $this->faker->randomElement(['draft', 'published']);

        $attributes = [
            'author_id' => User::query()->inRandomOrder()->first()?->id ?? User::factory(),
            'title' => $title,
            'slug' => (string) str($title)->slug(),
            'body' => $this->faker->paragraphs($this->faker->numberBetween(1, 3), true),
        ];

        return [
            ...$attributes,
            'draft' => $status === 'draft' ? $attributes : null,
            'status' => $status,
        ];
    }
}
