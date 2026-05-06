# SEO Suite Expansion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a complete Capell SEO Suite expansion with editor previews, scoring, expanded audits, redirect opportunities, internal-link suggestions, schema templates, Search Console insights, publish gates, and AI content briefs.

**Architecture:** SEO Suite owns SEO dashboard-dashboard_reports, previews, schema template registration, Search Console clients, internal-link suggestions, and AI briefs through Actions and Data objects. Redirect functionality remains in `capell-app/redirects`; SEO Suite only integrates with it through public Actions/contracts and graceful installed-package checks. PublishingStudio delegates SEO publish severity to SEO Suite report data instead of duplicating SEO logic.

**Tech Stack:** PHP 8.2, Laravel, Filament, Pest, Spatie Laravel Data, Lorisleiva Actions, Capell Core/Admin/Frontend package registries.

---

## File Structure

Create or modify these files. Keep each file focused on one responsibility.

### SEO Suite Core Report

- Create: `packages/seo-suite/src/Enums/SeoIssueSeverityEnum.php`
- Create: `packages/seo-suite/src/Enums/SeoCheckKeyEnum.php`
- Create: `packages/seo-suite/src/Enums/SeoCheckModeEnum.php`
- Create: `packages/seo-suite/src/Data/SeoIssueData.php`
- Create: `packages/seo-suite/src/Data/SeoPreviewData.php`
- Create: `packages/seo-suite/src/Data/PageSeoReportData.php`
- Create: `packages/seo-suite/src/Actions/CalculateSeoScoreAction.php`
- Create: `packages/seo-suite/src/Actions/BuildPageSeoReportAction.php`
- Modify: `packages/seo-suite/src/Actions/DashboardReports/BuildSEOAuditQueryAction.php`
- Modify: `packages/seo-suite/src/Filament/Pages/Tables/SEOAuditTable.php`

### Editor Panel

- Create: `packages/seo-suite/src/Filament/Components/Forms/Page/PageSeoPanel.php`
- Create: `packages/seo-suite/src/Filament/Extenders/Page/PageSeoPanelSchemaExtender.php`
- Create: `packages/seo-suite/resources/views/filament/components/page-seo-panel.blade.php`
- Modify: `packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php`
- Modify: `packages/seo-suite/resources/lang/en/form.php`
- Modify: `packages/seo-suite/resources/lang/en/generic.php`

### Redirect Integration

- Create: `packages/seo-suite/src/Data/RedirectOpportunityData.php`
- Create: `packages/seo-suite/src/Actions/BuildRedirectOpportunityReportAction.php`
- Create: `packages/seo-suite/src/Filament/Actions/CreateRedirectFromBrokenLinkAction.php`
- Modify: `packages/seo-suite/src/Filament/Pages/Tables/BrokenLinksTable.php`
- Modify: `packages/seo-suite/src/Filament/Pages/Tables/SEOAuditTable.php`
- Modify: `packages/redirects/src/Filament/Resources/Redirects/Tables/RedirectsTable.php`
- Modify: `packages/redirects/resources/lang/en/table.php`
- Modify: `packages/redirects/docs/redirects.md`

### Internal Links

- Create: `packages/seo-suite/src/Data/InternalLinkSuggestionData.php`
- Create: `packages/seo-suite/src/Actions/SuggestInternalLinksAction.php`
- Create: `packages/seo-suite/src/Support/InternalLinks/InternalLinkCandidateRepository.php`

### Schema Templates

- Create: `packages/seo-suite/src/Contracts/SchemaTemplate.php`
- Create: `packages/seo-suite/src/Data/SchemaTemplateReportData.php`
- Create: `packages/seo-suite/src/Enums/SchemaTemplateTypeEnum.php`
- Create: `packages/seo-suite/src/Support/SchemaTemplates/SchemaTemplateRegistry.php`
- Create: `packages/seo-suite/src/Support/SchemaTemplates/WebPageSchemaTemplate.php`
- Create: `packages/seo-suite/src/Support/SchemaTemplates/ArticleSchemaTemplate.php`
- Create: `packages/seo-suite/src/Actions/BuildSchemaTemplateReportAction.php`
- Modify: `packages/seo-suite/src/Actions/SchemaGraphAction.php`
- Modify: `packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php`

### Search Console

- Create: `packages/seo-suite/src/Contracts/SearchConsoleClientInterface.php`
- Create: `packages/seo-suite/src/Data/SearchConsoleInsightData.php`
- Create: `packages/seo-suite/src/Enums/SearchConsoleMetricEnum.php`
- Create: `packages/seo-suite/src/Support/SearchConsole/NullSearchConsoleClient.php`
- Create: `packages/seo-suite/src/Support/SearchConsole/GoogleSearchConsoleClient.php`
- Create: `packages/seo-suite/src/Actions/BuildPageSearchConsoleInsightsAction.php`
- Create: `packages/seo-suite/src/Actions/SyncSearchConsoleInsightsAction.php`
- Modify: `packages/seo-suite/config/capell-seo-suite.php`
- Modify: `packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php`

### Publish Gates

- Create: `packages/seo-suite/src/Contracts/SeoPublishReportProvider.php`
- Create: `packages/seo-suite/src/Support/Publishing/SeoPublishReportProviderAdapter.php`
- Modify: `packages/publishing-studio/src/Checks/SeoMetaCheck.php`
- Modify: `packages/publishing-studio/src/Checks/PublishCheckSeverity.php` only if it lacks blocker/warn/info levels needed by this integration.

### AI Briefs

- Create: `packages/seo-suite/src/Data/AiContentBriefData.php`
- Create: `packages/seo-suite/src/Actions/GenerateAiContentBriefAction.php`
- Create: `packages/seo-suite/src/Filament/Actions/AiContentBriefAction.php`
- Modify: `packages/seo-suite/src/Support/PromptRepository.php`
- Modify: `packages/seo-suite/config/capell-seo-suite.php`

### Tests and Docs

- Create tests under `packages/seo-suite/tests/Unit` and `packages/seo-suite/tests/Integration` matching each task below.
- Modify: `packages/seo-suite/README.md`
- Modify: `packages/seo-suite/docs/seo-meta-and-discoverability.md`
- Create: `packages/seo-suite/docs/seo-intelligence.md`
- Create: `packages/seo-suite/docs/search-console.md`
- Create: `packages/seo-suite/docs/schema-templates.md`
- Modify: `docs/openai-integration.md`

---

## Task 1: SEO Report Data, Enums, and Scoring

**Files:**

- Create: `packages/seo-suite/src/Enums/SeoIssueSeverityEnum.php`
- Create: `packages/seo-suite/src/Enums/SeoCheckKeyEnum.php`
- Create: `packages/seo-suite/src/Enums/SeoCheckModeEnum.php`
- Create: `packages/seo-suite/src/Data/SeoIssueData.php`
- Create: `packages/seo-suite/src/Data/SeoPreviewData.php`
- Create: `packages/seo-suite/src/Data/PageSeoReportData.php`
- Create: `packages/seo-suite/src/Actions/CalculateSeoScoreAction.php`
- Test: `packages/seo-suite/tests/Unit/Actions/CalculateSeoScoreActionTest.php`

- [ ] **Step 1: Write the failing scoring tests**

Create `packages/seo-suite/tests/Unit/Actions/CalculateSeoScoreActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\SeoSuite\Actions\CalculateSeoScoreAction;
use Capell\SeoSuite\Data\SeoIssueData;
use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;

it('calculates an explainable seo score from issue severity', function (): void {
    $score = CalculateSeoScoreAction::run([
        new SeoIssueData(
            key: SeoCheckKeyEnum::MetaTitle,
            severity: SeoIssueSeverityEnum::Critical,
            message: 'Missing meta title.',
        ),
        new SeoIssueData(
            key: SeoCheckKeyEnum::MetaDescription,
            severity: SeoIssueSeverityEnum::Warning,
            message: 'Meta description is short.',
        ),
        new SeoIssueData(
            key: SeoCheckKeyEnum::InternalLinks,
            severity: SeoIssueSeverityEnum::Notice,
            message: 'Add more internal links.',
        ),
    ]);

    expect($score)->toBe(62);
});

it('never returns a score below zero', function (): void {
    $issues = array_fill(
        0,
        8,
        new SeoIssueData(
            key: SeoCheckKeyEnum::Schema,
            severity: SeoIssueSeverityEnum::Critical,
            message: 'Critical issue.',
        ),
    );

    expect(CalculateSeoScoreAction::run($issues))->toBe(0);
});

it('returns full score when there are no issues', function (): void {
    expect(CalculateSeoScoreAction::run([]))->toBe(100);
});
```

- [ ] **Step 2: Run the scoring tests to verify they fail**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Unit/Actions/CalculateSeoScoreActionTest.php
```

Expected: FAIL because the new classes do not exist.

- [ ] **Step 3: Implement enums and data classes**

Create `SeoIssueSeverityEnum` with penalties and labels:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum SeoIssueSeverityEnum: string implements HasLabel
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Notice = 'notice';
    case Passed = 'passed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Critical => __('capell-seo-suite::generic.seo_severity_critical'),
            self::Warning => __('capell-seo-suite::generic.seo_severity_warning'),
            self::Notice => __('capell-seo-suite::generic.seo_severity_notice'),
            self::Passed => __('capell-seo-suite::generic.seo_severity_passed'),
        };
    }

    public function penalty(): int
    {
        return match ($this) {
            self::Critical => 25,
            self::Warning => 10,
            self::Notice => 3,
            self::Passed => 0,
        };
    }
}
```

Create `SeoCheckKeyEnum` with all check keys used by the release:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum SeoCheckKeyEnum: string implements HasLabel
{
    case MetaTitle = 'meta_title';
    case MetaDescription = 'meta_description';
    case DuplicateTitle = 'duplicate_title';
    case SocialImage = 'social_image';
    case Canonical = 'canonical';
    case Robots = 'robots';
    case ImageAltText = 'image_alt_text';
    case InternalLinks = 'internal_links';
    case Schema = 'schema';
    case BrokenLinks = 'broken_links';
    case Redirects = 'redirects';
    case TranslationCoverage = 'translation_coverage';
    case Sitemap = 'sitemap';
    case LlmsTxt = 'llms_txt';
    case SearchConsole = 'search_console';

    public function getLabel(): string
    {
        return __('capell-seo-suite::generic.seo_check_' . $this->value);
    }
}
```

Create `SeoCheckModeEnum`:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum SeoCheckModeEnum: string implements HasLabel
{
    case Blocker = 'blocker';
    case Warning = 'warning';
    case Ignored = 'ignored';

    public function getLabel(): string
    {
        return __('capell-seo-suite::generic.seo_check_mode_' . $this->value);
    }
}
```

Create `SeoIssueData`:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Data;

use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;
use Spatie\LaravelData\Data;

class SeoIssueData extends Data
{
    public function __construct(
        public SeoCheckKeyEnum $key,
        public SeoIssueSeverityEnum $severity,
        public string $message,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
    ) {}
}
```

Create `SeoPreviewData`:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Data;

use Spatie\LaravelData\Data;

class SeoPreviewData extends Data
{
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
        public ?string $imageUrl = null,
        public ?string $siteName = null,
    ) {}
}
```

Create `PageSeoReportData`:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Data;

use Spatie\LaravelData\Data;

class PageSeoReportData extends Data
{
    /**
     * @param list<SeoIssueData> $issues
     * @param list<SeoIssueData> $passedChecks
     */
    public function __construct(
        public int $score,
        public SeoPreviewData $searchPreview,
        public SeoPreviewData $socialPreview,
        public array $issues = [],
        public array $passedChecks = [],
        public array $internalLinkSuggestions = [],
        public array $schemaDashboardReports = [],
        public array $redirectOpportunities = [],
        public array $searchConsoleInsights = [],
    ) {}

    public function criticalCount(): int
    {
        return count(array_filter(
            $this->issues,
            static fn (SeoIssueData $issue): bool => $issue->severity->value === 'critical',
        ));
    }

    public function warningCount(): int
    {
        return count(array_filter(
            $this->issues,
            static fn (SeoIssueData $issue): bool => $issue->severity->value === 'warning',
        ));
    }
}
```

- [ ] **Step 4: Implement `CalculateSeoScoreAction`**

Create:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\SeoSuite\Data\SeoIssueData;
use Lorisleiva\Actions\Concerns\AsAction;

class CalculateSeoScoreAction
{
    use AsAction;

    /**
     * @param list<SeoIssueData> $issues
     */
    public function handle(array $issues): int
    {
        $penalty = array_reduce(
            $issues,
            static fn (int $carry, SeoIssueData $issue): int => $carry + $issue->severity->penalty(),
            0,
        );

        return max(0, 100 - $penalty);
    }
}
```

- [ ] **Step 5: Run the scoring tests to verify they pass**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Unit/Actions/CalculateSeoScoreActionTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/seo-suite/src/Enums/SeoIssueSeverityEnum.php \
  packages/seo-suite/src/Enums/SeoCheckKeyEnum.php \
  packages/seo-suite/src/Enums/SeoCheckModeEnum.php \
  packages/seo-suite/src/Data/SeoIssueData.php \
  packages/seo-suite/src/Data/SeoPreviewData.php \
  packages/seo-suite/src/Data/PageSeoReportData.php \
  packages/seo-suite/src/Actions/CalculateSeoScoreAction.php \
  packages/seo-suite/tests/Unit/Actions/CalculateSeoScoreActionTest.php
git commit -m "feat: add seo report scoring primitives"
```

---

## Task 2: Page SEO Report Action and Previews

**Files:**

- Create: `packages/seo-suite/src/Actions/BuildPageSeoReportAction.php`
- Modify: `packages/seo-suite/resources/lang/en/generic.php`
- Test: `packages/seo-suite/tests/Integration/Actions/BuildPageSeoReportActionTest.php`

- [ ] **Step 1: Write failing report tests**

Create `BuildPageSeoReportActionTest.php` with tests for missing meta, healthy page preview data, duplicate title, and noindex warning:

```php
<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\SeoSuite\Actions\BuildPageSeoReportAction;
use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;

it('dashboard-dashboard_reports critical issues for missing title and description', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->hasSiteDomain()->create();
    $page = PageFactory::new()->site($site)->withTranslations($language, ['meta' => []])->create();

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    expect($report->score)->toBeLessThan(100)
        ->and(collect($report->issues)->pluck('key'))->toContain(SeoCheckKeyEnum::MetaTitle)
        ->and(collect($report->issues)->pluck('key'))->toContain(SeoCheckKeyEnum::MetaDescription);
});

it('builds search and social previews from translation meta', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->hasSiteDomain()->create();
    $page = PageFactory::new()
        ->site($site)
        ->withTranslations($language, [
            'title' => 'Fallback Page Title',
            'meta' => [
                'title' => 'Search Title',
                'description' => 'Search description for the page.',
                'social_title' => 'Social Title',
                'social_description' => 'Social description for the page.',
            ],
        ])
        ->create();

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    expect($report->searchPreview->title)->toBe('Search Title')
        ->and($report->searchPreview->description)->toBe('Search description for the page.')
        ->and($report->socialPreview->title)->toBe('Social Title')
        ->and($report->socialPreview->description)->toBe('Social description for the page.');
});

it('flags duplicate meta titles in the same site and language', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->hasSiteDomain()->create();

    PageFactory::new()->site($site)->withTranslations($language, ['meta' => ['title' => 'Duplicate Title', 'description' => 'First description.']])->create();
    $page = PageFactory::new()->site($site)->withTranslations($language, ['meta' => ['title' => 'Duplicate Title', 'description' => 'Second description.']])->create();

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    expect(collect($report->issues)->pluck('key'))->toContain(SeoCheckKeyEnum::DuplicateTitle);
});

it('warns when robots directives noindex a page', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->hasSiteDomain()->create();
    $page = PageFactory::new()->site($site)->withTranslations($language, ['meta' => ['title' => 'Search Title', 'description' => 'Search description.']])->create([
        'meta' => ['robots' => ['noindex']],
    ]);

    $report = BuildPageSeoReportAction::run($page, $site, $language);

    $robotsIssue = collect($report->issues)->firstWhere('key', SeoCheckKeyEnum::Robots);

    expect($robotsIssue?->severity)->toBe(SeoIssueSeverityEnum::Warning);
});
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
vendor/bin/pest packages/seo-suite/tests/Integration/Actions/BuildPageSeoReportActionTest.php
```

Expected: FAIL because `BuildPageSeoReportAction` does not exist.

- [ ] **Step 3: Implement `BuildPageSeoReportAction`**

Create an Action that:

- Loads `translation`, `pageUrl`, `site`, and `translations`.
- Reads SEO values from `$page->translation?->meta`.
- Adds issues using `SeoIssueData`.
- Builds search/social previews using `SeoPreviewData`.
- Calls `CalculateSeoScoreAction`.
- Leaves extension arrays empty for now: internal links, schema, redirects, Search Console.

Use this method shape:

```php
public function handle(Page $page, Site $site, Language $language): PageSeoReportData
```

Use these helper methods:

```php
private function metaValue(Page $page, string $key): ?string
private function addLengthIssue(array &$issues, SeoCheckKeyEnum $key, ?string $value, int $minimum, int $maximum, string $missingMessage, string $shortMessage, string $longMessage): void
private function duplicateTitleExists(Page $page, Site $site, Language $language, string $title): bool
private function hasNoIndexDirective(Page $page): bool
```

- [ ] **Step 4: Add translation strings**

Add strings for issue labels and messages to `packages/seo-suite/resources/lang/en/generic.php`. Use `capell-seo-suite::generic.*` keys and keep all UI text package-owned.

- [ ] **Step 5: Run tests to verify the report passes**

```bash
vendor/bin/pest packages/seo-suite/tests/Integration/Actions/BuildPageSeoReportActionTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/seo-suite/src/Actions/BuildPageSeoReportAction.php \
  packages/seo-suite/resources/lang/en/generic.php \
  packages/seo-suite/tests/Integration/Actions/BuildPageSeoReportActionTest.php
git commit -m "feat: build page seo dashboard-dashboard_reports"
```

---

## Task 3: Editor SEO Panel and Expanded Audit Table

**Files:**

- Create: `packages/seo-suite/src/Filament/Components/Forms/Page/PageSeoPanel.php`
- Create: `packages/seo-suite/src/Filament/Extenders/Page/PageSeoPanelSchemaExtender.php`
- Create: `packages/seo-suite/resources/views/filament/components/page-seo-panel.blade.php`
- Modify: `packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php`
- Modify: `packages/seo-suite/src/Actions/DashboardReports/BuildSEOAuditQueryAction.php`
- Modify: `packages/seo-suite/src/Filament/Pages/Tables/SEOAuditTable.php`
- Test: `packages/seo-suite/tests/Feature/Filament/PageSeoPanelTest.php`
- Test: `packages/seo-suite/tests/Feature/Actions/DashboardReports/BuildSEOAuditQueryActionTest.php`

- [ ] **Step 1: Write failing tests**

Add a Filament schema extender test that resolves tagged `PageSchemaExtender::TAG` classes and asserts `PageSeoPanelSchemaExtender` is registered after package boot.

Add an audit table test that creates pages with healthy and unhealthy metadata, runs `BuildSEOAuditQueryAction::run()`, and asserts the unhealthy page appears.

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Filament/PageSeoPanelTest.php packages/seo-suite/tests/Feature/Actions/DashboardReports/BuildSEOAuditQueryActionTest.php
```

Expected: FAIL because the extender and expanded audit query are not implemented.

- [ ] **Step 3: Implement the panel component**

`PageSeoPanel` should extend a Filament component that can render a Blade view and receive page/site/language context from the edit form state. The component calls `BuildPageSeoReportAction::run()` and passes `PageSeoReportData` to the view.

The Blade view must render:

- Score.
- Search preview.
- Social preview.
- Critical/warning/notice sections.
- Passed checks count.
- Empty state when the page has not been saved yet.

- [ ] **Step 4: Register the panel extender**

Modify `SeoSuiteServiceProvider::registerPageSchemaExtenders()` to tag:

```php
PageSeoPanelSchemaExtender::class,
```

beside existing SEO extenders.

- [ ] **Step 5: Expand the audit table**

`BuildSEOAuditQueryAction` should still return a `Builder`, but the table should display report-derived columns with `formatStateUsing` closures that call `BuildPageSeoReportAction::run()` for the record's site/language.

Add columns:

- score
- critical count
- warning count
- schema status from report
- search preview title

Keep table queries site-scoped through `SiteScope::applyForCurrentActor()`.

- [ ] **Step 6: Run tests**

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Filament/PageSeoPanelTest.php packages/seo-suite/tests/Feature/Actions/DashboardReports/BuildSEOAuditQueryActionTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/seo-suite/src/Filament/Components/Forms/Page/PageSeoPanel.php \
  packages/seo-suite/src/Filament/Extenders/Page/PageSeoPanelSchemaExtender.php \
  packages/seo-suite/resources/views/filament/components/page-seo-panel.blade.php \
  packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php \
  packages/seo-suite/src/Actions/DashboardReports/BuildSEOAuditQueryAction.php \
  packages/seo-suite/src/Filament/Pages/Tables/SEOAuditTable.php \
  packages/seo-suite/tests/Feature/Filament/PageSeoPanelTest.php \
  packages/seo-suite/tests/Feature/Actions/DashboardReports/BuildSEOAuditQueryActionTest.php
git commit -m "feat: add editor seo panel and richer audit"
```

---

## Task 4: Redirect Opportunities and Redirect Manager SEO Improvements

**Files:**

- Create: `packages/seo-suite/src/Data/RedirectOpportunityData.php`
- Create: `packages/seo-suite/src/Actions/BuildRedirectOpportunityReportAction.php`
- Create: `packages/seo-suite/src/Filament/Actions/CreateRedirectFromBrokenLinkAction.php`
- Modify: `packages/seo-suite/src/Filament/Pages/Tables/BrokenLinksTable.php`
- Modify: `packages/redirects/src/Filament/Resources/Redirects/Tables/RedirectsTable.php`
- Modify: `packages/redirects/resources/lang/en/table.php`
- Test: `packages/seo-suite/tests/Integration/Actions/BuildRedirectOpportunityReportActionTest.php`
- Test: `packages/redirects/tests/Integration/Filament/RedirectsTableSEOColumnsTest.php`

- [ ] **Step 1: Write failing redirect opportunity tests**

Test that broken links with the same target URL are grouped into one `RedirectOpportunityData` with count, source URL, site id, language id, and suggested target URL set to null unless an existing page URL match is found.

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest packages/seo-suite/tests/Integration/Actions/BuildRedirectOpportunityReportActionTest.php
```

Expected: FAIL because the Action and Data do not exist.

- [ ] **Step 3: Implement redirect opportunity reporting**

`BuildRedirectOpportunityReportAction::handle(?int $siteId = null, ?int $languageId = null): array` should query `BrokenLink`, group by `target_url`, and return `RedirectOpportunityData` values.

`RedirectOpportunityData` fields:

```php
public string $sourceUrl;
public int $hits;
public ?int $siteId;
public ?int $languageId;
public ?string $suggestedTargetUrl;
public ?string $pageName;
```

- [ ] **Step 4: Add broken-link table action**

Add `CreateRedirectFromBrokenLinkAction` that:

- Checks `class_exists(\Capell\Redirects\Actions\ValidateRedirectAction::class)`.
- Opens a redirect creation URL or modal only when Redirects is installed.
- Uses Redirects validation messages.
- Does not write directly to `page_urls` from SEO Suite.

- [ ] **Step 5: Improve Redirects table columns**

Modify `RedirectsTable` to add:

- hit count bucket filter
- chain warning indicator using existing `ValidateRedirectAction`
- last hit visible by default

Do not perform live HTTP status checks during table rendering.

- [ ] **Step 6: Run redirect tests**

```bash
vendor/bin/pest packages/seo-suite/tests/Integration/Actions/BuildRedirectOpportunityReportActionTest.php packages/redirects/tests
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/seo-suite/src/Data/RedirectOpportunityData.php \
  packages/seo-suite/src/Actions/BuildRedirectOpportunityReportAction.php \
  packages/seo-suite/src/Filament/Actions/CreateRedirectFromBrokenLinkAction.php \
  packages/seo-suite/src/Filament/Pages/Tables/BrokenLinksTable.php \
  packages/redirects/src/Filament/Resources/Redirects/Tables/RedirectsTable.php \
  packages/redirects/resources/lang/en/table.php \
  packages/seo-suite/tests/Integration/Actions/BuildRedirectOpportunityReportActionTest.php \
  packages/redirects/tests/Integration/Filament/RedirectsTableSEOColumnsTest.php
git commit -m "feat: connect seo tools to redirect opportunities"
```

---

## Task 5: Internal-Link Suggestions

**Files:**

- Create: `packages/seo-suite/src/Data/InternalLinkSuggestionData.php`
- Create: `packages/seo-suite/src/Actions/SuggestInternalLinksAction.php`
- Create: `packages/seo-suite/src/Support/InternalLinks/InternalLinkCandidateRepository.php`
- Modify: `packages/seo-suite/src/Actions/BuildPageSeoReportAction.php`
- Test: `packages/seo-suite/tests/Integration/Actions/SuggestInternalLinksActionTest.php`

- [ ] **Step 1: Write failing suggestion tests**

Test that a page with content terms matching another page title receives a suggestion, and that the current page is excluded.

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest packages/seo-suite/tests/Integration/Actions/SuggestInternalLinksActionTest.php
```

Expected: FAIL because the Action does not exist.

- [ ] **Step 3: Implement suggestion Data and repository**

`InternalLinkSuggestionData`:

```php
public int $pageId;
public string $title;
public string $url;
public int $score;
public string $reason;
```

`InternalLinkCandidateRepository` should query published pages for the same site/language and return candidate title, URL, meta title, and meta description. It should not import Blog or Tags internals in the first implementation.

- [ ] **Step 4: Implement `SuggestInternalLinksAction`**

Tokenize the source page title, meta title, description, and extracted content text. Score candidates with:

- +5 for title term match.
- +3 for meta title term match.
- +1 for description term match.
- Exclude candidates with score 0.
- Return top 5 sorted by score descending.

- [ ] **Step 5: Wire suggestions into `BuildPageSeoReportAction`**

Set `$report->internalLinkSuggestions` from `SuggestInternalLinksAction::run($page, $site, $language)`.

- [ ] **Step 6: Run tests**

```bash
vendor/bin/pest packages/seo-suite/tests/Integration/Actions/SuggestInternalLinksActionTest.php packages/seo-suite/tests/Integration/Actions/BuildPageSeoReportActionTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/seo-suite/src/Data/InternalLinkSuggestionData.php \
  packages/seo-suite/src/Actions/SuggestInternalLinksAction.php \
  packages/seo-suite/src/Support/InternalLinks/InternalLinkCandidateRepository.php \
  packages/seo-suite/src/Actions/BuildPageSeoReportAction.php \
  packages/seo-suite/tests/Integration/Actions/SuggestInternalLinksActionTest.php
git commit -m "feat: suggest internal links for seo dashboard-dashboard_reports"
```

---

## Task 6: Schema Template Registry and DashboardReports

**Files:**

- Create: `packages/seo-suite/src/Contracts/SchemaTemplate.php`
- Create: `packages/seo-suite/src/Data/SchemaTemplateReportData.php`
- Create: `packages/seo-suite/src/Enums/SchemaTemplateTypeEnum.php`
- Create: `packages/seo-suite/src/Support/SchemaTemplates/SchemaTemplateRegistry.php`
- Create: `packages/seo-suite/src/Support/SchemaTemplates/WebPageSchemaTemplate.php`
- Create: `packages/seo-suite/src/Support/SchemaTemplates/ArticleSchemaTemplate.php`
- Create: `packages/seo-suite/src/Actions/BuildSchemaTemplateReportAction.php`
- Modify: `packages/seo-suite/src/Actions/SchemaGraphAction.php`
- Modify: `packages/seo-suite/src/Actions/BuildPageSeoReportAction.php`
- Modify: `packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php`
- Test: `packages/seo-suite/tests/Unit/Support/SchemaTemplateRegistryTest.php`
- Test: `packages/seo-suite/tests/Integration/Actions/BuildSchemaTemplateReportActionTest.php`

- [ ] **Step 1: Write failing schema registry tests**

Test that registry can register templates by `SchemaTemplateTypeEnum`, return them, and reject duplicate registration by replacing with the newest class only when explicitly called.

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest packages/seo-suite/tests/Unit/Support/SchemaTemplateRegistryTest.php packages/seo-suite/tests/Integration/Actions/BuildSchemaTemplateReportActionTest.php
```

Expected: FAIL because schema template classes do not exist.

- [ ] **Step 3: Implement schema template contract and enum**

`SchemaTemplate::build(Page $page, Site $site, Language $language): array`

`SchemaTemplate::requiredFields(Page $page, Site $site, Language $language): array`

`SchemaTemplateTypeEnum` cases:

- Article
- WebPage
- FAQ
- HowTo
- Event
- LocalBusiness
- Product
- Video
- Organization

- [ ] **Step 4: Implement registry and default templates**

Register `WebPageSchemaTemplate` and `ArticleSchemaTemplate` in `SeoSuiteServiceProvider`. The default templates should delegate to existing schema Actions where possible.

- [ ] **Step 5: Implement schema report Action**

`BuildSchemaTemplateReportAction` should return template type, present fields, missing fields, and severity. Missing required fields become warnings unless the current page type explicitly requires that schema type.

- [ ] **Step 6: Wire schema dashboard-dashboard_reports into page SEO dashboard-dashboard_reports and graph**

`BuildPageSeoReportAction` should include schema dashboard-dashboard_reports. `SchemaGraphAction` should ask the registry for matching templates and merge valid template nodes into the graph without duplicating existing WebPage/Article nodes.

- [ ] **Step 7: Run tests**

```bash
vendor/bin/pest packages/seo-suite/tests/Unit/Support/SchemaTemplateRegistryTest.php packages/seo-suite/tests/Integration/Actions/BuildSchemaTemplateReportActionTest.php packages/seo-suite/tests/Integration/Actions/SchemaGraphActionTest.php
```

Expected: PASS. If `SchemaGraphActionTest.php` does not exist, run the existing schema integration tests in `packages/seo-suite/tests/Integration/Actions`.

- [ ] **Step 8: Commit**

```bash
git add packages/seo-suite/src/Contracts/SchemaTemplate.php \
  packages/seo-suite/src/Data/SchemaTemplateReportData.php \
  packages/seo-suite/src/Enums/SchemaTemplateTypeEnum.php \
  packages/seo-suite/src/Support/SchemaTemplates \
  packages/seo-suite/src/Actions/BuildSchemaTemplateReportAction.php \
  packages/seo-suite/src/Actions/SchemaGraphAction.php \
  packages/seo-suite/src/Actions/BuildPageSeoReportAction.php \
  packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php \
  packages/seo-suite/tests/Unit/Support/SchemaTemplateRegistryTest.php \
  packages/seo-suite/tests/Integration/Actions/BuildSchemaTemplateReportActionTest.php
git commit -m "feat: add schema template registry"
```

---

## Task 7: Search Console Insights

**Files:**

- Create: `packages/seo-suite/src/Contracts/SearchConsoleClientInterface.php`
- Create: `packages/seo-suite/src/Data/SearchConsoleInsightData.php`
- Create: `packages/seo-suite/src/Enums/SearchConsoleMetricEnum.php`
- Create: `packages/seo-suite/src/Support/SearchConsole/NullSearchConsoleClient.php`
- Create: `packages/seo-suite/src/Support/SearchConsole/GoogleSearchConsoleClient.php`
- Create: `packages/seo-suite/src/Actions/BuildPageSearchConsoleInsightsAction.php`
- Create: `packages/seo-suite/src/Actions/SyncSearchConsoleInsightsAction.php`
- Modify: `packages/seo-suite/config/capell-seo-suite.php`
- Modify: `packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php`
- Modify: `packages/seo-suite/src/Actions/BuildPageSeoReportAction.php`
- Test: `packages/seo-suite/tests/Unit/SearchConsole/NullSearchConsoleClientTest.php`
- Test: `packages/seo-suite/tests/Unit/Actions/BuildPageSearchConsoleInsightsActionTest.php`

- [ ] **Step 1: Write failing Null client tests**

Test that `NullSearchConsoleClient` returns setup-required insights and never throws.

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest packages/seo-suite/tests/Unit/SearchConsole/NullSearchConsoleClientTest.php packages/seo-suite/tests/Unit/Actions/BuildPageSearchConsoleInsightsActionTest.php
```

Expected: FAIL because classes do not exist.

- [ ] **Step 3: Implement Search Console contract and Null client**

`SearchConsoleClientInterface` methods:

```php
public function isConfigured(): bool;
public function pageInsights(string $url): array;
public function decliningPages(int $siteId, int $limit = 10): array;
```

`NullSearchConsoleClient` returns empty arrays and `false` from `isConfigured()`.

- [ ] **Step 4: Implement config and provider binding**

Add `search_console` config:

```php
'search_console' => [
    'enabled' => env('CAPELL_SEO_TOOLS_SEARCH_CONSOLE_ENABLED', false),
    'credentials_path' => env('CAPELL_SEO_TOOLS_SEARCH_CONSOLE_CREDENTIALS'),
],
```

Bind `SearchConsoleClientInterface` to `NullSearchConsoleClient` unless enabled and credentials are configured. Add `GoogleSearchConsoleClient` as a thin adapter with constructor-injected config and no live API call in tests.

- [ ] **Step 5: Implement insight Actions**

`BuildPageSearchConsoleInsightsAction` should:

- Resolve page URL.
- Return setup-required insight when client is not configured.
- Return client page insights when configured.

`SyncSearchConsoleInsightsAction` should call the client and provide a command-friendly count result without persisting until a storage model is added.

- [ ] **Step 6: Wire insights into page SEO report**

Set `$report->searchConsoleInsights` from `BuildPageSearchConsoleInsightsAction::run($page)`.

- [ ] **Step 7: Run tests**

```bash
vendor/bin/pest packages/seo-suite/tests/Unit/SearchConsole/NullSearchConsoleClientTest.php packages/seo-suite/tests/Unit/Actions/BuildPageSearchConsoleInsightsActionTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add packages/seo-suite/src/Contracts/SearchConsoleClientInterface.php \
  packages/seo-suite/src/Data/SearchConsoleInsightData.php \
  packages/seo-suite/src/Enums/SearchConsoleMetricEnum.php \
  packages/seo-suite/src/Support/SearchConsole \
  packages/seo-suite/src/Actions/BuildPageSearchConsoleInsightsAction.php \
  packages/seo-suite/src/Actions/SyncSearchConsoleInsightsAction.php \
  packages/seo-suite/config/capell-seo-suite.php \
  packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php \
  packages/seo-suite/src/Actions/BuildPageSeoReportAction.php \
  packages/seo-suite/tests/Unit/SearchConsole/NullSearchConsoleClientTest.php \
  packages/seo-suite/tests/Unit/Actions/BuildPageSearchConsoleInsightsActionTest.php
git commit -m "feat: add search console insight boundary"
```

---

## Task 8: Publishing Gates Integration

**Files:**

- Create: `packages/seo-suite/src/Contracts/SeoPublishReportProvider.php`
- Create: `packages/seo-suite/src/Support/Publishing/SeoPublishReportProviderAdapter.php`
- Modify: `packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php`
- Modify: `packages/publishing-studio/src/Checks/SeoMetaCheck.php`
- Test: `packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php`

- [ ] **Step 1: Write failing publish check tests**

Test that `SeoMetaCheck` uses `SeoPublishReportProvider` when available and maps:

- Critical SEO issue to blocker/error severity.
- Warning SEO issue to warn severity.
- No issues to info severity.

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php
```

Expected: FAIL because the provider contract does not exist and `SeoMetaCheck` still perform-builder direct DB checks.

- [ ] **Step 3: Implement SEO publish provider contract**

`SeoPublishReportProvider::forWorkspace(Workspace $workspace): array`

Adapter should query workspace pages, run `BuildPageSeoReportAction`, and return issue data grouped by page.

- [ ] **Step 4: Bind provider in SEO Suite**

In `SeoSuiteServiceProvider`, bind `SeoPublishReportProvider::class` to `SeoPublishReportProviderAdapter::class`.

- [ ] **Step 5: Update PublishingStudio check**

`SeoMetaCheck` should:

- Use `app()->bound(SeoPublishReportProvider::class)` safely.
- Fall back to its current DB check when SEO Suite is unavailable.
- Convert critical issues to the highest available publish severity.
- Convert warnings/notices to warning messages.

- [ ] **Step 6: Run tests**

```bash
vendor/bin/pest packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php packages/seo-suite/tests/Integration/Actions/BuildPageSeoReportActionTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/seo-suite/src/Contracts/SeoPublishReportProvider.php \
  packages/seo-suite/src/Support/Publishing/SeoPublishReportProviderAdapter.php \
  packages/seo-suite/src/Providers/SeoSuiteServiceProvider.php \
  packages/publishing-studio/src/Checks/SeoMetaCheck.php \
  packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php
git commit -m "feat: use seo dashboard-dashboard_reports for publish checks"
```

---

## Task 9: AI Content Briefs

**Files:**

- Create: `packages/seo-suite/src/Data/AiContentBriefData.php`
- Create: `packages/seo-suite/src/Actions/GenerateAiContentBriefAction.php`
- Create: `packages/seo-suite/src/Filament/Actions/AiContentBriefAction.php`
- Modify: `packages/seo-suite/src/Support/PromptRepository.php`
- Modify: `packages/seo-suite/config/capell-seo-suite.php`
- Modify: `packages/seo-suite/src/Filament/Components/Forms/Page/PageSeoPanel.php`
- Test: `packages/seo-suite/tests/Unit/Actions/GenerateAiContentBriefActionTest.php`

- [ ] **Step 1: Write failing AI brief tests**

Test that fake AI response JSON is parsed into:

- suggested content angle
- missing topics
- suggested headings
- FAQ ideas
- schema opportunities
- internal links
- meta alternatives

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest packages/seo-suite/tests/Unit/Actions/GenerateAiContentBriefActionTest.php
```

Expected: FAIL because AI brief classes do not exist.

- [ ] **Step 3: Implement `AiContentBriefData`**

Fields:

```php
public string $contentAngle;
public array $missingTopics;
public array $suggestedHeadings;
public array $faqIdeas;
public array $schemaOpportunities;
public array $internalLinks;
public array $metaTitleAlternatives;
public array $metaDescriptionAlternatives;
```

- [ ] **Step 4: Implement `GenerateAiContentBriefAction`**

Use existing AI services:

- `PromptRepository`
- `PrismProvider`
- `AiRateLimiter`
- `RecordAiGenerationAction`

The Action should accept `Page $page, Site $site, Language $language`, build context from `BuildPageSeoReportAction`, request JSON, parse with `AiResponseParser`, and return `AiContentBriefData`.

- [ ] **Step 5: Add prompt config**

Add an `ai_content_brief` prompt to `capell-seo-suite.php`. Require JSON output and include the report fields.

- [ ] **Step 6: Add Filament action**

`AiContentBriefAction` should appear in the SEO panel, open a modal, run the Action, display suggestions, and never write content automatically.

- [ ] **Step 7: Run tests**

```bash
vendor/bin/pest packages/seo-suite/tests/Unit/Actions/GenerateAiContentBriefActionTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add packages/seo-suite/src/Data/AiContentBriefData.php \
  packages/seo-suite/src/Actions/GenerateAiContentBriefAction.php \
  packages/seo-suite/src/Filament/Actions/AiContentBriefAction.php \
  packages/seo-suite/src/Support/PromptRepository.php \
  packages/seo-suite/config/capell-seo-suite.php \
  packages/seo-suite/src/Filament/Components/Forms/Page/PageSeoPanel.php \
  packages/seo-suite/tests/Unit/Actions/GenerateAiContentBriefActionTest.php
git commit -m "feat: generate ai seo content briefs"
```

---

## Task 10: Documentation, Arch Tests, and Final Verification

**Files:**

- Modify: `packages/seo-suite/README.md`
- Modify: `packages/seo-suite/docs/seo-meta-and-discoverability.md`
- Create: `packages/seo-suite/docs/seo-intelligence.md`
- Create: `packages/seo-suite/docs/search-console.md`
- Create: `packages/seo-suite/docs/schema-templates.md`
- Modify: `packages/redirects/docs/redirects.md`
- Modify: `docs/openai-integration.md`
- Modify: `packages/seo-suite/tests/Arch/SeoSuiteBoundaryTest.php`

- [ ] **Step 1: Update documentation**

Document:

- SEO score and issue severities.
- Editor SEO panel.
- Audit table.
- Redirect integration with existing Redirects package.
- Internal-link suggestions.
- Schema template registry.
- Search Console setup states.
- Publish gates.
- AI content brief safety model.

- [ ] **Step 2: Extend arch tests**

Update `SeoSuiteBoundaryTest` so SEO Suite is allowed to use public Redirects contracts/actions only in redirect integration classes, and does not import Blog, Tags, Search, or PublishingStudio internals from core report classes.

- [ ] **Step 3: Run focused package tests**

```bash
vendor/bin/pest packages/seo-suite/tests
vendor/bin/pest packages/redirects/tests
vendor/bin/pest packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php
```

Expected: PASS.

- [ ] **Step 4: Run package integration tests**

```bash
vendor/bin/pest tests/Packages
```

Expected: PASS.

- [ ] **Step 5: Run preflight**

```bash
composer preflight
```

Expected: PASS.

- [ ] **Step 6: Commit docs and final checks**

```bash
git add packages/seo-suite/README.md \
  packages/seo-suite/docs/seo-meta-and-discoverability.md \
  packages/seo-suite/docs/seo-intelligence.md \
  packages/seo-suite/docs/search-console.md \
  packages/seo-suite/docs/schema-templates.md \
  packages/redirects/docs/redirects.md \
  docs/openai-integration.md \
  packages/seo-suite/tests/Arch/SeoSuiteBoundaryTest.php
git commit -m "docs: document seo tools expansion"
```

---

## Self-Review Notes

- Spec coverage: every approved feature maps to at least one task.
- Redirect scope: redirects remain in `capell-app/redirects`; SEO Suite integrates through public APIs and installed-package checks.
- Type consistency: all planned classes use `Capell\SeoSuite` except Redirects table updates and PublishingStudio publish check integration.
- Test strategy: starts with Action tests, then Filament/admin integration, then cross-package package tests.
- Risk: the editor panel depends on the exact Filament schema context available in page edit form-builder. If the form context cannot provide saved page/site/language reliably, implement the panel first as a page header action/modal and then move it inline once the context hook is confirmed.
