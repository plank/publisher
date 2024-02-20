<?php

namespace Plank\Publisher\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Plank\Publisher\Publisher
 */
class Publisher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Plank\Publisher\Publisher::class;
    }
}
