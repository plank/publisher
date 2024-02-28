<?php

namespace Plank\Publisher\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Plank\Publisher\Contracts\Publishable;

/**
 * @method static bool shouldEnableDraftContent(Request $request)
 * @method static bool draftContentAllowed()
 * @method static bool draftContentRestricted()
 * @method static void allowDraftContent()
 * @method static void restrictDraftContent()
 * @method static Collection<Model&Publishable> publishableModels()
 */
class Publisher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'publisher';
    }
}
