<?php

namespace Plank\Publisher\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plank\Publisher\Facades\Publisher;

class PublisherMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Publisher::shouldEnableDraftContent($request)) {
            Publisher::allowDraftContent();
        }

        return $next($request);
    }
}
