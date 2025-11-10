<?php

declare(strict_types=1);

use Capell\Blog\Models\Tag;
use Capell\Layout\Models\Content;

it('has many tags', function (): void {
    $content = Content::factory()->create();
    $tag = Tag::factory()->create();

    $content->tags()->attach($tag);

    expect($content->tags->pluck('id'))->toContain($tag->id);
});
