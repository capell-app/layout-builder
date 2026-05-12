<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\BuildPageSeoReportAction;
use Capell\SeoSuite\Actions\PersistPageSeoSnapshotAction;
use Capell\SeoSuite\Actions\RefreshPageSeoSnapshotAction;
use Capell\SeoSuite\Actions\RefreshSiteSeoSnapshotsAction;
use Capell\SeoSuite\Data\InternalLinkSuggestionData;
use Capell\SeoSuite\Data\PageSeoReportData;
use Capell\SeoSuite\Data\SchemaTemplateReportData;
use Capell\SeoSuite\Data\SeoIssueData;
use Capell\SeoSuite\Data\SeoPreviewData;
use Capell\SeoSuite\Enums\SchemaTemplateTypeEnum;
use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;
use Capell\SeoSuite\Models\PageSeoSnapshot;
use Capell\SeoSuite\Settings\SeoSuiteSettings;

it('upserts a compact page seo snapshot from a report', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language)->create();

    $report = new PageSeoReportData(
        score: 65,
        searchPreview: new SeoPreviewData(
            title: 'About Capell',
            description: 'A search preview description.',
            url: 'https://example.com/about',
        ),
        socialPreview: new SeoPreviewData(
            title: 'About Capell',
            description: 'A social preview description.',
            url: 'https://example.com/about',
        ),
        issues: [
            new SeoIssueData(
                key: SeoCheckKeyEnum::MetaTitle,
                severity: SeoIssueSeverityEnum::Critical,
                message: 'Meta title is missing.',
            ),
            new SeoIssueData(
                key: SeoCheckKeyEnum::Schema,
                severity: SeoIssueSeverityEnum::Warning,
                message: 'Schema is missing.',
            ),
        ],
        passedChecks: [
            SeoCheckKeyEnum::MetaDescription,
        ],
        internalLinkSuggestions: [
            new InternalLinkSuggestionData(
                pageId: (int) $page->getKey(),
                title: 'Related page',
                url: 'https://example.com/related',
                score: 80,
                reason: 'Relevant topic overlap.',
            ),
        ],
    );

    $firstSnapshot = PersistPageSeoSnapshotAction::run($page, $site, $language, $report);
    $secondSnapshot = PersistPageSeoSnapshotAction::run($page, $site, $language, $report);

    expect(PageSeoSnapshot::query()->count())->toBe(1)
        ->and($secondSnapshot->is($firstSnapshot))->toBeTrue()
        ->and($secondSnapshot->score)->toBe(65)
        ->and($secondSnapshot->critical_count)->toBe(1)
        ->and($secondSnapshot->warning_count)->toBe(1)
        ->and($secondSnapshot->issue_keys)->toBe(['meta_title', 'schema'])
        ->and($secondSnapshot->passed_check_keys)->toBe(['meta_description'])
        ->and($secondSnapshot->computed_at)->not()->toBeNull();
});

it('records passed checks provided as seo issue data', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language)->create();

    $report = new PageSeoReportData(
        score: 100,
        searchPreview: new SeoPreviewData(
            title: 'About Capell',
            description: 'A search preview description.',
            url: 'https://example.com/about',
        ),
        socialPreview: new SeoPreviewData(
            title: 'About Capell',
            description: 'A social preview description.',
            url: 'https://example.com/about',
        ),
        passedChecks: [
            new SeoIssueData(
                key: SeoCheckKeyEnum::MetaDescription,
                severity: SeoIssueSeverityEnum::Passed,
                message: 'Meta description is present.',
            ),
        ],
    );

    $snapshot = PersistPageSeoSnapshotAction::run($page, $site, $language, $report);

    expect($snapshot->passed_count)->toBe(1)
        ->and($snapshot->passed_check_keys)->toBe(['meta_description']);
});

it('marks schema status as warning when a schema report has missing fields', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language)->create();

    $report = new PageSeoReportData(
        score: 75,
        searchPreview: new SeoPreviewData(
            title: 'About Capell',
            description: 'A search preview description.',
            url: 'https://example.com/about',
        ),
        socialPreview: new SeoPreviewData(
            title: 'About Capell',
            description: 'A social preview description.',
            url: 'https://example.com/about',
        ),
        schemaDashboardReports: [
            new SchemaTemplateReportData(
                templateType: SchemaTemplateTypeEnum::WebPage,
                presentFields: ['name'],
                missingFields: ['description'],
                severity: SeoIssueSeverityEnum::Warning,
            ),
        ],
    );

    $snapshot = PersistPageSeoSnapshotAction::run($page, $site, $language, $report);

    expect($snapshot->schema_status)->toBe('warning');
});

it('refreshes a single page seo snapshot from the canonical report action', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, [
            'content' => '',
            'meta' => [],
            'title' => 'A',
        ])
        ->create();

    $snapshot = RefreshPageSeoSnapshotAction::run($page, $site, $language);

    expect($snapshot->page_id)->toBe($page->getKey())
        ->and($snapshot->site_id)->toBe($site->getKey())
        ->and($snapshot->language_id)->toBe($language->getKey())
        ->and($snapshot->critical_count)->toBeGreaterThan(0);
});

it('honours disabled seo audit settings when building page reports', function (): void {
    $this->registerAndMigrateSettings(
        [
            '2026_05_10_190871_03_create_seo_suite_settings',
            '2026_05_10_190871_04_update_seo_suite_settings_add_ai_discovery',
        ],
        dirname(__DIR__, 3) . '/database/settings',
    );

    $settings = resolve(SeoSuiteSettings::class);
    $settings->seo_audit_enabled = false;
    $settings->save();

    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language, [
            'content' => '',
            'meta' => [],
            'title' => 'A',
        ])
        ->create();

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    expect($report->issues)->toBe([])
        ->and($report->passedChecks)->toBe([]);
});

it('refreshes seo snapshots for every page in a site', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();

    Page::factory()
        ->count(3)
        ->site($site)
        ->withTranslations($language, [
            'content' => '',
            'meta' => [],
            'title' => 'A',
        ])
        ->create();

    $result = RefreshSiteSeoSnapshotsAction::run($site, $language, 2);

    expect($result)->toBe(['refreshed' => 3])
        ->and(PageSeoSnapshot::query()->where('site_id', $site->getKey())->count())->toBe(3);
});
