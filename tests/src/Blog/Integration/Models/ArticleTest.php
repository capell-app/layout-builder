<?php

declare(strict_types=1);

use Capell\Blog\Models\Article;
use Capell\Core\Models\Site;

it('finds next and previous siblings by publish date', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $olderArticle = Article::factory()->site($site)->create([
        'visible_from' => '2025-01-01 00:00:00',
        'created_at' => '2025-01-01 00:00:00',
    ]);

    $currentArticle = Article::factory()->site($site)->create([
        'visible_from' => '2025-01-02 00:00:00',
        'created_at' => '2025-01-02 00:00:00',
    ]);

    $newerArticle = Article::factory()->site($site)->create([
        'visible_from' => '2025-01-03 00:00:00',
        'created_at' => '2025-01-03 00:00:00',
    ]);

    expect($currentArticle->prevSiblings()->first()?->is($olderArticle))->toBeTrue()
        ->and($currentArticle->nextSiblings()->first()?->is($newerArticle))->toBeTrue();
});

it('falls back to created_at when visible_from is null and keeps siblings in the same site', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $firstArticle = Article::factory()->site($site)->create([
        'visible_from' => null,
        'created_at' => '2025-02-01 00:00:00',
    ]);

    $secondArticle = Article::factory()->site($site)->create([
        'visible_from' => null,
        'created_at' => '2025-02-02 00:00:00',
    ]);

    $differentSite = Site::factory()->withTranslations()->create();

    Article::factory()->site($differentSite)->create([
        'visible_from' => null,
        'created_at' => '2025-02-03 00:00:00',
    ]);

    expect($firstArticle->prevSiblings()->first())->toBeNull()
        ->and($firstArticle->nextSiblings()->first()?->is($secondArticle))->toBeTrue();
});
