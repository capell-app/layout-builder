<?php

declare(strict_types=1);

use Capell\ContentSections\Models\Section;
use Capell\Tags\Models\Tag;

it('has many tags', function (): void {
    $content = Section::factory()->create();
    $tag = Tag::factory()->create();

    $content->tags()->attach($tag);

    expect($content->tags->pluck('id'))->toContain($tag->id);
});
