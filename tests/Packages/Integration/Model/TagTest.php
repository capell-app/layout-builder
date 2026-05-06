<?php

declare(strict_types=1);

use Capell\ContentSections\Models\Section;
use Capell\Tags\Models\Tag;

it('can be attached to sections', function (): void {
    $tag = Tag::factory()->create();
    $section = Section::factory()->create();

    $tag->sections()->attach($section);

    expect($tag->sections)->toHaveCount(1)
        ->and($tag->sections->first()->id)->toBe($section->id);
});
