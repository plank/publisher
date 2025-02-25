<?php

namespace Plank\Publisher\Tests\Helpers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plank\Publisher\Enums\Status;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\Section;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition()
    {
        $heading = $this->faker->words($this->faker->numberBetween(2, 4), true);
        $status = $this->faker->randomElement(Status::cases());

        $attributes = [
            'post_id' => Post::query()->inRandomOrder()->first()?->id ?? Post::factory(),
            'heading' => $heading,
            'text' => $this->faker->paragraphs($this->faker->numberBetween(1, 3), true),
        ];

        return [
            ...$attributes,
            'draft' => $status === 'draft' ? $attributes : null,
            'status' => $status,
        ];
    }
}
