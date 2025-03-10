<?php

use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\Section;

it('correctly lists all publishable models', function () {
    expect(Publisher::publishableModels())
        ->toContain(Post::class)
        ->toContain(Section::class);
});
