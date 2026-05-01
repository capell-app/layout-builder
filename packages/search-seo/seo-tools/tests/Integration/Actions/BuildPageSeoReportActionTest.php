<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\SeoTools\Actions\BuildPageSeoReportAction;
use Capell\SeoTools\Enums\SeoCheckKeyEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
use Illuminate\Contracts\Database\Eloquent\Builder;

it('reports critical issues for missing title and description', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language, ['meta' => []])->create();

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    expect($report->score)->toBeLessThan(100)
        ->and(collect($report->issues)->pluck('key'))->toContain(SeoCheckKeyEnum::MetaTitle)
        ->and(collect($report->issues)->pluck('key'))->toContain(SeoCheckKeyEnum::MetaDescription);
});

it('builds search and social previews from translation meta', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'meta' => [
                'title' => 'Search Title',
                'description' => 'Search description for the page.',
                'social_title' => 'Social Title',
                'social_description' => 'Social description for the page.',
            ],
        ])
        ->create();
    $page->translations()->where('language_id', $language->id)->update(['title' => 'Fallback Page Title']);

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    expect($report->searchPreview->title)->toBe('Search Title')
        ->and($report->searchPreview->description)->toBe('Search description for the page.')
        ->and($report->socialPreview->title)->toBe('Social Title')
        ->and($report->socialPreview->description)->toBe('Social description for the page.');
});

it('flags duplicate meta titles in the same site and language', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();

    PageFactory::new()->site($site)->withTranslations($language, ['meta' => ['title' => 'Duplicate Title', 'description' => 'First description.']])->create();
    $page = PageFactory::new()->site($site)->withTranslations($language, ['meta' => ['title' => 'Duplicate Title', 'description' => 'Second description.']])->create();

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    expect(collect($report->issues)->pluck('key'))->toContain(SeoCheckKeyEnum::DuplicateTitle);
});

it('warns when robots directives noindex a page', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language, ['meta' => ['title' => 'Search Title', 'description' => 'Search description.']])->create([
        'meta' => ['robots' => ['noindex']],
    ]);

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    $robotsIssue = collect($report->issues)->firstWhere('key', SeoCheckKeyEnum::Robots);

    expect($robotsIssue?->severity)->toBe(SeoIssueSeverityEnum::Warning);
});

it('reloads language scoped relations for the requested language', function (): void {
    $english = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $french = LanguageFactory::new()->create(['name' => 'French', 'code' => 'fr']);
    $site = SiteFactory::new()
        ->recycle($english)
        ->language($english)
        ->withTranslations([$english, $french])
        ->create();
    $page = PageFactory::new()
        ->site($site)
        ->withTranslations([
            $english,
            $french,
        ], [
            $english->id => [
                'meta' => [
                    'title' => 'English Search Title',
                    'description' => 'English search description for the page.',
                ],
            ],
            $french->id => [
                'meta' => [
                    'title' => 'French Search Title',
                    'description' => 'French search description for the page.',
                ],
            ],
        ])
        ->create();

    $page->load([
        'translation' => fn (Builder $query): Builder => $query->where('language_id', $english->id),
        'pageUrl' => fn (Builder $query): Builder => $query->where('language_id', $english->id),
    ]);
    $site->load([
        'translation' => fn (Builder $query): Builder => $query->where('language_id', $english->id),
    ]);

    $report = BuildPageSeoReportAction::run($page, $site, $french);

    expect($report->searchPreview->title)->toBe('French Search Title')
        ->and($report->searchPreview->description)->toBe('French search description for the page.');
});
