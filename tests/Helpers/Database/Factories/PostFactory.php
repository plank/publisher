<?php

namespace Plank\Publisher\Tests\Helpers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plank\Publisher\Enums\Status;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition()
    {
        $title = $this->faker->words($this->faker->numberBetween(2, 4), true);
        $status = $this->faker->randomElement(Status::cases());

        $attributes = [
            'author_id' => User::query()->inRandomOrder()->first()?->id ?? User::factory(),
            'title' => $title,
            'subtitle' => $this->faker->words(3, true),
            'slug' => (string) str($title)->slug(),
            'teaser' => $this->faker->paragraphs(1, true),
            'body' => $this->faker->paragraphs($this->faker->numberBetween(1, 5), true),
        ];

        return [
            ...$attributes,
            'draft' => $status === 'draft' ? $attributes : null,
            'status' => $status,
        ];
    }
}
