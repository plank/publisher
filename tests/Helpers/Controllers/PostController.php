<?php

namespace Tests\Helpers\Controllers;

use Illuminate\Routing\Controller;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        if (Publisher::draftContentAllowed()) {
            return 'Draft Content Index';
        }

        return 'Published Content Index';
    }

    public function show($id)
    {
        return Post::findOrFail($id);
    }
}
