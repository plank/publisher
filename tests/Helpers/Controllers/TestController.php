<?php

namespace Tests\Helpers\Controllers;

use Illuminate\Routing\Controller;
use Plank\Publisher\Facades\Publisher;

class TestController extends Controller
{
    public function test()
    {
        if (Publisher::draftContentAllowed()) {
            return response('Draft Content Visible');
        }

        return response('Draft Content Restricted');
    }
}
