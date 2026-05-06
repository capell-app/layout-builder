<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\SeoSuite\Actions\SuggestInternalLinksAction;
use Capell\SeoSuite\Data\InternalLinkSuggestionData;

it('suggests pages whose titles and metadata match the source page topic', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $sourcePage = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'Website strategy',
            'content' => '<p>Conversion planning and content architecture for growth.</p>',
            'meta' => [
                'title' => 'Conversion planning guide',
                'description' => 'Improve content architecture across the website.',
            ],
        ])
        ->create();
    $candidatePage = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'Conversion Architecture',
            'meta' => [
                'title' => 'Website planning',
                'description' => 'Architecture for content-led growth.',
            ],
        ])
        ->create();

    PageUrl::factory()->page($sourcePage)->site($site)->language($language)->state(['url' => '/strategy'])->create();
    PageUrl::factory()->page($candidatePage)->site($site)->language($language)->state(['url' => '/conversion-architecture'])->create();

    $suggestions = SuggestInternalLinksAction::run($sourcePage, $site, $language);

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0])->toBeInstanceOf(InternalLinkSuggestionData::class)
        ->and($suggestions[0]->pageId)->toBe($candidatePage->id)
        ->and($suggestions[0]->title)->toBe('Conversion Architecture')
        ->and($suggestions[0]->url)->toBe('/conversion-architecture')
        ->and($suggestions[0]->score)->toBeGreaterThan(0);
});

it('excludes the current page even when its terms match', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $sourcePage = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'Search optimisation',
            'content' => '<p>Search optimisation planning.</p>',
            'meta' => [
                'title' => 'Search optimisation',
                'description' => 'Search optimisation planning.',
            ],
        ])
        ->create();

    PageUrl::factory()->page($sourcePage)->site($site)->language($language)->state(['url' => '/search-optimisation'])->create();

    $suggestions = SuggestInternalLinksAction::run($sourcePage, $site, $language);

    expect($suggestions)->toBe([]);
});

it('does not use the source meta title as a matching token source', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $sourcePage = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'Editorial planning',
            'content' => '<p>Audience research and production cadence.</p>',
            'meta' => [
                'title' => 'Excluded launchpad phrase',
                'description' => 'Audience research for the editorial calendar.',
            ],
        ])
        ->create();
    $candidatePage = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'Launchpad operations',
        ])
        ->create();

    PageUrl::factory()->page($sourcePage)->site($site)->language($language)->state(['url' => '/editorial-planning'])->create();
    PageUrl::factory()->page($candidatePage)->site($site)->language($language)->state(['url' => '/launchpad-operations'])->create();

    $suggestions = SuggestInternalLinksAction::run($sourcePage, $site, $language);

    expect($suggestions)->toBe([]);
});

it('excludes pages that should not be public internal-link targets', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $sourcePage = PageFactory::new()
        ->site($site)
        ->published()
        ->withTranslations($language, [
            'title' => 'Technical search optimisation',
            'content' => '<p>Canonical optimisation and crawl planning.</p>',
            'meta' => [
                'description' => 'Canonical optimisation and crawl planning.',
            ],
        ])
        ->create();
    $hiddenPage = PageFactory::new()
        ->site($site)
        ->published()
        ->meta('hidden', true)
        ->withTranslations($language, ['title' => 'Canonical optimisation'])
        ->create();
    $noindexPage = PageFactory::new()
        ->site($site)
        ->published()
        ->meta('robots', ['noindex'])
        ->withTranslations($language, ['title' => 'Crawl planning'])
        ->create();
    $pendingPage = PageFactory::new()
        ->site($site)
        ->pending()
        ->withTranslations($language, ['title' => 'Technical optimisation'])
        ->create();

    PageUrl::factory()->page($sourcePage)->site($site)->language($language)->state(['url' => '/technical-search'])->create();

    foreach ([$hiddenPage, $noindexPage, $pendingPage] as $candidatePage) {
        PageUrl::factory()
            ->page($candidatePage)
            ->site($site)
            ->language($language)
            ->state(['url' => '/' . str($candidatePage->name)->slug()])
            ->create();
    }

    $suggestions = SuggestInternalLinksAction::run($sourcePage, $site, $language);

    expect($suggestions)->toBe([]);
});

it('uses enabled non-redirect page urls for suggestions', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $sourcePage = PageFactory::new()
        ->site($site)
        ->published()
        ->withTranslations($language, [
            'title' => 'Canonical optimisation',
            'content' => '<p>Canonical planning.</p>',
            'meta' => [
                'description' => 'Canonical planning.',
            ],
        ])
        ->create();
    $candidatePage = PageFactory::new()
        ->site($site)
        ->published()
        ->withTranslations($language, ['title' => 'Canonical planning'])
        ->create();

    PageUrl::factory()->page($sourcePage)->site($site)->language($language)->state(['url' => '/canonical'])->create();
    PageUrl::factory()
        ->page($candidatePage)
        ->site($site)
        ->language($language)
        ->state(['url' => '/disabled-canonical', 'status' => false])
        ->create();
    PageUrl::factory()
        ->page($candidatePage)
        ->site($site)
        ->language($language)
        ->state(['url' => '/redirect-canonical', 'type' => UrlTypeEnum::Redirect])
        ->create();
    PageUrl::factory()
        ->page($candidatePage)
        ->site($site)
        ->language($language)
        ->state(['url' => '/canonical-planning'])
        ->create();

    $suggestions = SuggestInternalLinksAction::run($sourcePage, $site, $language);

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0]->url)->toBe('/canonical-planning');
});

it('limits suggestions to five and orders ties by title', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $sourcePage = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'Insights planning',
            'content' => '<p>Insights planning for reporting.</p>',
            'meta' => [
                'title' => 'Insights planning',
                'description' => 'Insights planning for reporting.',
            ],
        ])
        ->create();

    PageUrl::factory()->page($sourcePage)->site($site)->language($language)->state(['url' => '/insights-planning'])->create();

    foreach (['Delta Insights', 'Bravo Insights', 'Echo Insights', 'Alpha Insights', 'Foxtrot Insights', 'Charlie Insights'] as $candidateTitle) {
        $candidatePage = PageFactory::new()
            ->site($site)
            ->withTranslations($language, [
                'title' => $candidateTitle,
                'meta' => [
                    'title' => 'Reporting',
                    'description' => 'Planning notes.',
                ],
            ])
            ->create();

        PageUrl::factory()
            ->page($candidatePage)
            ->site($site)
            ->language($language)
            ->state(['url' => '/' . str($candidateTitle)->slug()])
            ->create();
    }

    $suggestions = SuggestInternalLinksAction::run($sourcePage, $site, $language);

    expect($suggestions)->toHaveCount(5)
        ->and(collect($suggestions)->pluck('title')->all())->toBe([
            'Alpha Insights',
            'Bravo Insights',
            'Charlie Insights',
            'Delta Insights',
            'Echo Insights',
        ]);
});
