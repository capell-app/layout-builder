<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\SeoTools\Data\PageSeoReportData;
use Capell\SeoTools\Data\RedirectOpportunityData;
use Capell\SeoTools\Data\SeoIssueData;
use Capell\SeoTools\Data\SeoPreviewData;
use Capell\SeoTools\Enums\SeoCheckKeyEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
use Capell\SeoTools\Filament\Components\Forms\Page\PageSeoPanel;
use Capell\SeoTools\Filament\Extenders\Page\PageSeoPanelSchemaExtender;
use Filament\Schemas\Schema;

it('registers the page SEO panel schema extender', function (): void {
    $extenders = collect(app()->tagged(PageSchemaExtender::TAG));

    expect($extenders->contains(
        fn (PageSchemaExtender $extender): bool => $extender instanceof PageSeoPanelSchemaExtender,
    ))->toBeTrue();
});

it('adds the page SEO panel after search meta', function (): void {
    $extender = resolve(PageSeoPanelSchemaExtender::class);
    $components = $extender->extendTranslationComponentsForHook(
        Schema::make(),
        PageTranslationSchemaHookEnum::AfterSearchMeta,
    );

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(PageSeoPanel::class);
});

it('groups SEO report issues and passed checks by key and severity', function (): void {
    $report = new PageSeoReportData(
        score: 72,
        searchPreview: new SeoPreviewData(
            title: 'Search title',
            description: 'Search description',
            url: 'https://example.test/search-page',
        ),
        socialPreview: new SeoPreviewData(
            title: 'Social title',
            description: 'Social description',
            url: 'https://example.test/social-page',
            imageUrl: 'https://example.test/social-image.jpg',
        ),
        issues: [
            new SeoIssueData(
                key: SeoCheckKeyEnum::MetaTitle,
                severity: SeoIssueSeverityEnum::Critical,
                message: 'Meta title needs attention.',
            ),
            new SeoIssueData(
                key: SeoCheckKeyEnum::Schema,
                severity: SeoIssueSeverityEnum::Warning,
                message: 'Schema needs attention.',
            ),
            new SeoIssueData(
                key: SeoCheckKeyEnum::InternalLinks,
                severity: SeoIssueSeverityEnum::Notice,
                message: 'Internal links need attention.',
            ),
        ],
        passedChecks: [
            SeoCheckKeyEnum::MetaDescription,
        ],
    );

    expect($report->issuesBySeverity(SeoIssueSeverityEnum::Critical))->toHaveCount(1)
        ->and($report->issuesForKey(SeoCheckKeyEnum::Schema))->toHaveCount(1)
        ->and($report->passedCheckValues())->toBe(['meta_description'])
        ->and($report->hasIssuesForKey(SeoCheckKeyEnum::InternalLinks))->toBeTrue();
});

it('exposes redirect opportunities to the page SEO panel view data', function (): void {
    $report = new PageSeoReportData(
        score: 72,
        searchPreview: new SeoPreviewData(
            title: 'Search title',
            description: 'Search description',
            url: 'https://example.test/search-page',
        ),
        socialPreview: new SeoPreviewData(
            title: 'Social title',
            description: 'Social description',
            url: 'https://example.test/social-page',
        ),
        redirectOpportunities: [
            new RedirectOpportunityData(
                sourceUrl: 'https://example.test/old-page',
                hits: 12,
                siteId: 1,
                languageId: 1,
                suggestedTargetUrl: 'https://example.test/new-page',
                pageName: 'New page',
            ),
        ],
    );

    $reflection = new ReflectionClass(PageSeoPanel::class);
    $method = $reflection->getMethod('viewDataForReport');
    $method->setAccessible(true);

    $viewData = $method->invoke(PageSeoPanel::make(), $report);

    expect($viewData['redirectOpportunities'])->toHaveCount(1)
        ->and($viewData['redirectOpportunities'][0])->toBeInstanceOf(RedirectOpportunityData::class)
        ->and($viewData['redirectOpportunities'][0]->sourceUrl)->toBe('https://example.test/old-page');
});
