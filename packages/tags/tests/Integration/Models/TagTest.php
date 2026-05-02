<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tags\Models\Tag;

it('belongs to a site', function (): void {
    $site = Site::factory()->create();
    $tag = Tag::factory()->create(['site_id' => $site->id]);

    expect($tag->site)->toBeInstanceOf(Site::class)
        ->and($tag->site->id)->toBe($site->id);
});

it('has featured and status attributes', function (): void {
    $tag = Tag::factory()->create(['featured' => true, 'status' => false]);

    expect($tag->featured)->toBeTrue()
        ->and($tag->status)->toBeFalse();
});

it('can be attached to pages', function (): void {
    $tag = Tag::factory()->create();
    $page = Page::factory()->create();

    $tag->pages()->attach($page);

    expect($tag->pages)->toHaveCount(1)
        ->and($tag->pages->first()->id)->toBe($page->id);
});

it('persists missing locale translations when falling back to the default locale', function (): void {
    app()->setLocale('en');

    $tag = Tag::factory()->create([
        'name' => ['en' => 'Latest News'],
        'slug' => ['en' => 'latest-news'],
        'type' => 'page',
    ]);

    $resolvedTag = Tag::findOrCreateFromString('Latest News', 'page', 'cy');

    expect($resolvedTag->getKey())->toBe($tag->getKey())
        ->and($resolvedTag->fresh()->getTranslation('name', 'cy'))->toBe('Latest News')
        ->and($resolvedTag->fresh()->getTranslation('slug', 'cy'))->toBe('latest-news');
});
