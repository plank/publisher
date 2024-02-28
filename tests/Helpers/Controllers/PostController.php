<?php

namespace Tests\Helpers\Controllers;

use Illuminate\Routing\Controller;
use Plank\Publisher\Tests\Helpers\Models\Post;

class PostController extends Controller
{
    public function show($id)
    {
        return Post::findOrFail($id);
    }
}
