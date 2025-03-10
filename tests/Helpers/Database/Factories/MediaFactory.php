<?php

namespace Plank\Publisher\Tests\Helpers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plank\Publisher\Tests\Helpers\Models\Media;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition()
    {
        return [
            'title' => $title = $this->faker->words(3, true),
            'file' => str($title)->slug()->toString().$this->faker->randomElement(['.png', '.pdf', '.zip']),
        ];
    }
}
