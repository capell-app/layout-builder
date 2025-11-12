<?php

declare(strict_types=1);

use Capell\Blog\Models\Tag;
use Capell\Core\Models\Site;

it('has many tags', function (): void {
    $site = Site::factory()->create();
    $tag = Tag::factory()->create(['site_id' => $site->id]);

    expect($site->tags->pluck('id'))->toContain($tag->id);
});
