<?php

declare(strict_types=1);

use Capell\Blog\Models\Tag;

it('can be attached to contents', function (): void {
    $tag = Tag::factory()->create();
    $content = Collection::factory()->create();

    $tag->contents()->attach($content);

    expect($tag->contents)->toHaveCount(1)
        ->and($tag->contents->first()->id)->toBe($content->id);
});
