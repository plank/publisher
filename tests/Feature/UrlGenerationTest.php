<?php

use Plank\Publisher\Facades\Publisher;

it('appends the previewKey when user draft content is enabled', function () {
    Publisher::allowDraftContent();

    expect(url(''))->toEqual(config()->get('app.url').'?preview=true');
    expect(url('?foo=bar'))->toEqual(config()->get('app.url').'?foo=bar&preview=true');
});

it('does not append the previewKey when user draft content is disabled', function () {
    Publisher::restrictDraftContent();

    expect(url(''))->toEqual(config()->get('app.url'));
    expect(url('?foo=bar'))->toEqual(config()->get('app.url').'?foo=bar');
});
