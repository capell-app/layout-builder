<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\SeoTools\Actions\Reports\BuildSEOAuditQueryAction;
use Capell\SeoTools\Filament\Pages\Tables\SEOAuditTable;

it('includes pages with missing SEO metadata and excludes healthy metadata', function (): void {
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
        ->not->toContain($healthyPage->getKey());
});

it('uses the unhealthy translation language for audit report columns', function (): void {
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

    $reflectionMethod = new ReflectionMethod(SEOAuditTable::class, 'reportFor');

    $report = $reflectionMethod->invoke(null, $auditedPage);

    expect($report?->searchPreview->title)->not->toBe('A healthy English search title')
        ->and($report?->criticalCount())->toBeGreaterThan(0);
});
