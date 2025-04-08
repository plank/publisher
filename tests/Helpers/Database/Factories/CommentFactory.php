<?php

namespace Plank\Publisher\Tests\Helpers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Plank\Publisher\Tests\Helpers\Models\Comment;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\User;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'body' => $this->faker->paragraphs(2, true),
            'upvotes' => $this->faker->numberBetween(0, 100),
        ];
    }
}
