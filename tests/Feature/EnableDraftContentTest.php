<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Plank\Publisher\Facades\Publisher;

it('should not enable draft when user cannot view-draft-content', function () {
    // Rebind the view-draft-content gate
    Gate::define('view-draft-content', function ($user) {
        return false;
    });

    $url = url('');
    expect($url)->toBe(config()->get('app.url'));

    $request = Request::create($url);
    expect(Publisher::shouldEnableDraftContent($request))->toBeFalse();

    $request = Request::create($url.'?preview=true');
    expect(Publisher::shouldEnableDraftContent($request))->toBeFalse();
});

it('should not enable draft content when no previewKey is provided', function () {
    $url = config()->get('app.url');
    $request = Request::create($url);
    expect(Publisher::shouldEnableDraftContent($request))->toBeFalse();
});

it('should enable draft content when previewKey is provided', function () {
    $url = config()->get('app.url').'?preview=true';
    $request = Request::create($url);
    expect(Publisher::shouldEnableDraftContent($request))->toBeTrue();
});
