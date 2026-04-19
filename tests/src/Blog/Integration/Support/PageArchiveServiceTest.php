<?php

declare(strict_types=1);

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Blog\Enums\BlogTypeGroupEnum;
use Capell\Blog\Models\Article;
use Capell\Blog\Support\Creator\ArticleCreator;
use Capell\Blog\Support\PageArchiveService;
use Capell\Core\Models\Site;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function (): void {
    $articleCreator = resolve(ArticleCreator::class);
    $articleCreator->createArticlePageType();
});

it('returns archive counts grouped by year and month', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    Article::factory()
        ->site($site)
        ->language($language)
        ->create([
            'visible_from' => '2025-03-15 10:00:00',
        ]);

    Article::factory()
        ->site($site)
        ->language($language)
        ->create([
            'visible_from' => '2025-03-20 10:00:00',
        ]);

    Article::factory()
        ->site($site)
        ->language($language)
        ->create([
            'visible_from' => '2025-02-10 10:00:00',
        ]);

    $service = resolve(PageArchiveService::class);
    $archives = $service->getArchivedCountsByMonth(
        $site,
        $language,
        BlogTypeGroupEnum::Blog->value,
    );

    expect($archives)->toHaveCount(2)
        ->and($archives[0])->toBeInstanceOf(ArchiveMonthData::class)
        ->year->toBe(2025)
        ->month->toBe(3)
        ->total->toBe(2)
        ->and($archives[1])
        ->year->toBe(2025)
        ->month->toBe(2)
        ->total->toBe(1);
});

it('returns paginated archive counts', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    for ($i = 0; $i < 20; $i++) {
        Article::factory()
            ->site($site)
            ->language($language)
            ->create([
                'visible_from' => now()->subMonths($i)->startOfMonth(),
            ]);
    }

    $service = resolve(PageArchiveService::class);
    $archives = $service->getArchivedCountsByMonth(
        $site,
        $language,
        BlogTypeGroupEnum::Blog->value,
        paginate: true,
        perPage: 5,
    );

    expect($archives)->toBeInstanceOf(LengthAwarePaginator::class)
        ->toHaveCount(5);
});

it('falls back to created_at when visible_from is null', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $language = $site->translations->first()->language;

    Article::factory()
        ->site($site)
        ->language($language)
        ->create([
            'visible_from' => null,
            'created_at' => '2025-01-15 10:00:00',
        ]);

    $service = resolve(PageArchiveService::class);
    $archives = $service->getArchivedCountsByMonth(
        $site,
        $language,
        BlogTypeGroupEnum::Blog->value,
    );

    expect($archives)->toHaveCount(1)
        ->and($archives[0]->year)->toBe(2025)
        ->and($archives[0]->month)->toBe(1);
});
