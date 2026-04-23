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
