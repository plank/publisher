<?php

namespace Plank\Publisher\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool draftContentAllowed()
 * @method static bool draftContentRestricted()
 * @method static void allowDraftContent()
 * @method static void restrictDraftContent()
 */
class Publisher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'publisher';
    }
}
