<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\SeoTools\Actions\Reports\BuildSEOAuditQueryAction;
use Capell\SeoTools\Filament\Pages\Tables\SEOAuditTable;
use Capell\SeoTools\Models\PageSeoSnapshot;

it('includes healthy and unhealthy pages in the site wide seo audit query', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $healthyPage = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'meta' => [
                'title' => 'A healthy search title for this content page',
                'description' => 'A healthy search description that gives search engines a useful summary.',
            ],
        ])
        ->create();
    $unhealthyPage = PageFactory::new()
        ->site($site)
        ->withTranslations($language, ['meta' => []])
        ->create();

    $pageIds = BuildSEOAuditQueryAction::run()->pluck('id')->all();

    expect($pageIds)
        ->toContain($unhealthyPage->getKey())
        ->toContain($healthyPage->getKey());
});

it('exposes snapshot backed seo audit filters', function (): void {
    $reflectionClass = new ReflectionClass(SEOAuditTable::class);
    $reflectionMethod = $reflectionClass->getMethod('getTableFilters');
    $filters = collect($reflectionMethod->invoke(null));

    expect($filters->map(fn (mixed $filter): string => $filter->getName())->all())->toContain(
        'severity',
        'issue_key',
        'score_band',
        'schema_status',
        'robots_status',
        'canonical_status',
        'has_redirect_opportunities',
        'search_console_status',
        'snapshot_state',
    );
});

it('uses the unhealthy translation language for snapshot backed audit columns', function (): void {
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
                    'title' => 'A healthy English search title',
                    'description' => 'A healthy English search description for this content page.',
                ],
            ],
            $french->id => [
                'meta' => [
                    'title' => '',
                    'description' => '',
                ],
            ],
        ])
        ->create();

    $auditedPage = BuildSEOAuditQueryAction::run()
        ->whereKey($page->getKey())
        ->firstOrFail();

    PageSeoSnapshot::query()->create([
        'page_id' => $page->getKey(),
        'site_id' => $site->getKey(),
        'language_id' => $english->getKey(),
        'score' => 100,
        'critical_count' => 0,
        'warning_count' => 0,
        'notice_count' => 0,
        'passed_count' => 1,
        'schema_status' => 'passed',
        'robots_status' => 'passed',
        'canonical_status' => 'passed',
        'redirect_opportunities_count' => 0,
        'search_console_status' => 'unknown',
        'computed_at' => now(),
    ]);

    PageSeoSnapshot::query()->create([
        'page_id' => $page->getKey(),
        'site_id' => $site->getKey(),
        'language_id' => $french->getKey(),
        'score' => 50,
        'critical_count' => 2,
        'warning_count' => 1,
        'notice_count' => 0,
        'passed_count' => 0,
        'schema_status' => 'missing',
        'robots_status' => 'passed',
        'canonical_status' => 'passed',
        'redirect_opportunities_count' => 0,
        'search_console_status' => 'unknown',
        'computed_at' => now(),
    ]);

    $reflectionMethod = new ReflectionMethod(SEOAuditTable::class, 'snapshotFor');

    $snapshot = $reflectionMethod->invoke(null, $auditedPage);

    expect($snapshot)->toBeInstanceOf(PageSeoSnapshot::class)
        ->and($snapshot->language_id)->toBe($french->getKey())
        ->and($snapshot->critical_count)->toBe(2);
});
