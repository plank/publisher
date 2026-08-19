<?php

use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\CountShadow;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('excludes attributes shadowed by a public model property from the draft', function () {
    $model = new CountShadow;

    // Mimic a model hydrated with a withCount alias (`cut_sheets_count`) alongside
    // real columns. setRawAttributes writes straight into $attributes, bypassing the
    // public property — exactly what Eloquent's withCount hydration does.
    $model->setRawAttributes([
        'id' => 1,
        'title' => 'A Post',
        'cut_sheets_count' => 7,   // camelCase relation `cutSheets` -> isCount() misses it
    ], sync: true);

    $model->putAttributesInDraft();

    expect($model->draft)->toHaveKey('title', 'A Post')
        ->and($model->draft)->not->toHaveKey('cut_sheets_count');
});

it('still excludes conventionally named relationship counts via isCount()', function () {
    $model = new CountShadow;

    $model->setRawAttributes([
        'id' => 1,
        'title' => 'A Post',
        'comments_count' => 3,     // snake relation `comments` -> isCount() catches it
    ], sync: true);

    $model->putAttributesInDraft();

    expect($model->draft)->not->toHaveKey('comments_count');
});

it('does not exclude columns that collide with protected Eloquent internals', function () {
    $model = new CountShadow;

    // `with` and `connection` are protected properties on Model; a real column of
    // that name must still be drafted (the guard only skips public model properties).
    $model->setRawAttributes([
        'id' => 1,
        'title' => 'A Post',
        'with' => 'a value',
        'connection' => 'a value',
    ], sync: true);

    $model->putAttributesInDraft();

    expect($model->draft)->toHaveKey('with', 'a value')
        ->and($model->draft)->toHaveKey('connection', 'a value');
});
