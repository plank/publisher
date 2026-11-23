<?php

use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Tests\Helpers\Models\Post;
use Plank\Publisher\Tests\Helpers\Models\Section;
use Plank\Publisher\Tests\Helpers\Models\User;

beforeEach(function () {
    Publisher::allowDraftContent();
});

it('stages a value typed into a field the published record left empty', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'subtitle' => null,
        'status' => 'published',
    ]);

    Publisher::withDraftContent(function () use ($post) {
        $post->status = 'draft';
        $post->subtitle = 'Drafted subtitle';
        $post->save();
    });

    $stored = Publisher::withoutDraftContent(fn () => Post::query()
        ->withoutGlobalScopes()
        ->find($post->id));

    expect($stored->getRawOriginal('subtitle'))->toBeNull()
        ->and($stored->draft['subtitle'])->toBe('Drafted subtitle');
});

it('stages a value typed into an empty field of an already drafted record', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'subtitle' => null,
        'status' => 'published',
    ]);

    Publisher::withDraftContent(function () use ($post) {
        $post->status = 'draft';
        $post->title = 'Drafted title';
        $post->save();
    });

    // Loaded while drafted, so the published values come from `publishedAttributes`
    $drafted = Post::query()
        ->withoutGlobalScopes()
        ->find($post->id);

    Publisher::withDraftContent(function () use ($drafted) {
        $drafted->subtitle = 'Drafted subtitle';
        $drafted->save();
    });

    $stored = Publisher::withoutDraftContent(fn () => Post::query()
        ->withoutGlobalScopes()
        ->find($post->id));

    expect($stored->getRawOriginal('subtitle'))->toBeNull()
        ->and($stored->draft['subtitle'])->toBe('Drafted subtitle');
});

it('stages a value typed into an empty field of a publishing dependent', function () {
    $post = Post::factory()->create([
        'author_id' => User::first()->id,
        'status' => 'published',
    ]);

    $section = Section::factory()->create([
        'post_id' => $post->id,
        'subheading' => null,
        'status' => 'published',
    ]);

    Publisher::withDraftContent(function () use ($section) {
        $section->status = 'draft';
        $section->subheading = 'Drafted subheading';
        $section->save();
    });

    $stored = Publisher::withoutDraftContent(fn () => Section::query()
        ->withoutGlobalScopes()
        ->find($section->id));

    expect($stored->getRawOriginal('subheading'))->toBeNull()
        ->and($stored->draft['subheading'])->toBe('Drafted subheading');
});
