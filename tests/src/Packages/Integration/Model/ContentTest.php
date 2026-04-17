<?php

declare(strict_types=1);

use Capell\Blog\Models\Tag;

it('has many tags', function (): void {
    $content = Collection::factory()->create();
    $tag = Tag::factory()->create();

    $content->tags()->attach($tag);

    expect($content->tags->pluck('id'))->toContain($tag->id);
});
