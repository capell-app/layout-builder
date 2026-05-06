# SEO Intelligence Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Harden Capell SEO Suite with queryable SEO snapshots, richer editor reporting, prefilled redirect creation, cached redirect health, stored Search Console metrics, and config-backed publish gates without turning the package into a crawler or insights platform.

**Architecture:** `BuildPageSeoReportAction` stays the canonical single-page report builder. New snapshot and metric tables store only compact, queryable projections for admin filtering and trend reporting. Redirect creation and redirect health stay in the Redirects package; SEO Suite integrates through public Actions and optional class checks.

**Tech Stack:** PHP 8.2, Laravel Eloquent migrations/models, Filament tables/actions/form-builder, Pest, Spatie Laravel Data, Lorisleiva Actions, Capell Core/Admin package patterns.

---

## File Structure

### SEO Suite Snapshots and Audit

- Create: `packages/seo-suite/database/migrations/create_page_seo_snapshots_table.php`
- Create: `packages/seo-suite/src/Models/PageSeoSnapshot.php`
- Create: `packages/seo-suite/src/Enums/SeoSnapshotStatusEnum.php`
- Create: `packages/seo-suite/src/Actions/PersistPageSeoSnapshotAction.php`
- Create: `packages/seo-suite/src/Actions/RefreshPageSeoSnapshotAction.php`
- Create: `packages/seo-suite/src/Actions/RefreshSiteSeoSnapshotsAction.php`
- Modify: `packages/seo-suite/src/Data/PageSeoReportData.php`
- Modify: `packages/seo-suite/src/Actions/DashboardReports/BuildSEOAuditQueryAction.php`
- Modify: `packages/seo-suite/src/Filament/Pages/Tables/SEOAuditTable.php`
- Test: `packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php`
- Test: `packages/seo-suite/tests/Feature/Actions/DashboardReports/BuildSEOAuditQueryActionTest.php`

### Editor Panel

- Modify: `packages/seo-suite/src/Filament/Components/Forms/Page/PageSeoPanel.php`
- Modify: `packages/seo-suite/resources/views/filament/components/page-seo-panel.blade.php`
- Modify: `packages/seo-suite/resources/lang/en/generic.php`
- Test: `packages/seo-suite/tests/Feature/Filament/PageSeoPanelTest.php`

### Redirect Defaults and Health

- Create: `packages/redirects/database/migrations/create_redirect_health_snapshots_table.php`
- Create: `packages/redirects/src/Models/RedirectHealthSnapshot.php`
- Create: `packages/redirects/src/Actions/BuildRedirectCreateUrlAction.php`
- Create: `packages/redirects/src/Actions/RefreshRedirectHealthSnapshotAction.php`
- Create: `packages/redirects/src/Actions/RefreshRedirectHealthSnapshotsAction.php`
- Modify: `packages/redirects/src/Filament/Resources/Redirects/Pages/ManageRedirects.php`
- Modify: `packages/redirects/src/Filament/Resources/Redirects/Tables/RedirectsTable.php`
- Modify: `packages/seo-suite/src/Filament/Actions/CreateRedirectFromBrokenLinkAction.php`
- Modify: `packages/redirects/resources/lang/en/table.php`
- Test: `packages/redirects/tests/Integration/Actions/BuildRedirectCreateUrlActionTest.php`
- Test: `packages/redirects/tests/Integration/Actions/RedirectHealthSnapshotActionTest.php`
- Test: `packages/redirects/tests/Integration/Filament/RedirectsTableSEOColumnsTest.php`
- Test: `packages/seo-suite/tests/Integration/Actions/CreateRedirectForBrokenLinkActionTest.php`

### Search Console Metrics

- Create: `packages/seo-suite/database/migrations/create_search_console_url_metrics_table.php`
- Create: `packages/seo-suite/src/Models/SearchConsoleUrlMetric.php`
- Create: `packages/seo-suite/src/Actions/PersistSearchConsoleUrlMetricAction.php`
- Modify: `packages/seo-suite/src/Contracts/SearchConsoleClientInterface.php`
- Modify: `packages/seo-suite/src/Support/SearchConsole/GoogleSearchConsoleClient.php`
- Modify: `packages/seo-suite/src/Support/SearchConsole/NullSearchConsoleClient.php`
- Modify: `packages/seo-suite/src/Actions/SyncSearchConsoleInsightsAction.php`
- Modify: `packages/seo-suite/src/Actions/BuildPageSearchConsoleInsightsAction.php`
- Test: `packages/seo-suite/tests/Unit/SearchConsole/GoogleSearchConsoleClientTest.php`
- Test: `packages/seo-suite/tests/Unit/SearchConsole/NullSearchConsoleClientTest.php`
- Test: `packages/seo-suite/tests/Feature/Actions/SyncSearchConsoleInsightsActionTest.php`

### Publish Gates

- Modify: `packages/seo-suite/config/capell-seo-suite.php`
- Modify: `packages/seo-suite/src/Support/Publishing/SeoPublishReportProviderAdapter.php`
- Modify: `packages/publishing-studio/src/Checks/SeoMetaCheck.php`
- Test: `packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php`

---

## Task 1: Add Page SEO Snapshot Storage

**Files:**

- Create: `packages/seo-suite/database/migrations/create_page_seo_snapshots_table.php`
- Create: `packages/seo-suite/src/Models/PageSeoSnapshot.php`
- Create: `packages/seo-suite/src/Enums/SeoSnapshotStatusEnum.php`
- Create: `packages/seo-suite/src/Actions/PersistPageSeoSnapshotAction.php`
- Test: `packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php`

- [ ] **Step 1: Write the failing snapshot persistence test**

Create `packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\SeoSuite\Actions\PersistPageSeoSnapshotAction;
use Capell\SeoSuite\Data\PageSeoReportData;
use Capell\SeoSuite\Data\SeoIssueData;
use Capell\SeoSuite\Data\SeoPreviewData;
use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;
use Capell\SeoSuite\Models\PageSeoSnapshot;

it('upserts one seo snapshot per page site and language', function (): void {
    $language = LanguageFactory::new()->create(['code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();
    $report = new PageSeoReportData(
        score: 65,
        searchPreview: new SeoPreviewData(title: 'Title', description: 'Description', url: '/page'),
        socialPreview: new SeoPreviewData(title: 'Social', description: 'Social description', url: '/page'),
        issues: [
            new SeoIssueData(
                key: SeoCheckKeyEnum::MetaTitle,
                severity: SeoIssueSeverityEnum::Critical,
                message: 'Missing title.',
            ),
            new SeoIssueData(
                key: SeoCheckKeyEnum::Schema,
                severity: SeoIssueSeverityEnum::Warning,
                message: 'Missing schema.',
            ),
        ],
        passedChecks: [SeoCheckKeyEnum::MetaDescription],
        internalLinkSuggestions: ['one'],
        schemaDashboardReports: [],
        redirectOpportunities: ['one', 'two'],
        searchConsoleInsights: [],
    );

    $snapshot = PersistPageSeoSnapshotAction::run($page, $site, $language, $report);
    $updatedSnapshot = PersistPageSeoSnapshotAction::run($page, $site, $language, $report);

    expect(PageSeoSnapshot::query()->count())->toBe(1)
        ->and($updatedSnapshot->is($snapshot))->toBeTrue()
        ->and($updatedSnapshot->score)->toBe(65)
        ->and($updatedSnapshot->critical_count)->toBe(1)
        ->and($updatedSnapshot->warning_count)->toBe(1)
        ->and($updatedSnapshot->issue_keys)->toBe([SeoCheckKeyEnum::MetaTitle->value, SeoCheckKeyEnum::Schema->value])
        ->and($updatedSnapshot->passed_check_keys)->toBe([SeoCheckKeyEnum::MetaDescription->value])
        ->and($updatedSnapshot->redirect_opportunities_count)->toBe(2)
        ->and($updatedSnapshot->computed_at)->not->toBeNull();
});
```

- [ ] **Step 2: Run the failing test**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php
```

Expected: FAIL because `PageSeoSnapshot` and `PersistPageSeoSnapshotAction` do not exist.

- [ ] **Step 3: Add the migration**

Create `packages/seo-suite/database/migrations/create_page_seo_snapshots_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_seo_snapshots')) {
            return;
        }

        Schema::create('page_seo_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->unsignedSmallInteger('critical_count')->default(0);
            $table->unsignedSmallInteger('warning_count')->default(0);
            $table->unsignedSmallInteger('notice_count')->default(0);
            $table->unsignedSmallInteger('passed_count')->default(0);
            $table->json('issue_keys')->nullable();
            $table->json('passed_check_keys')->nullable();
            $table->string('schema_status')->default('unknown');
            $table->string('robots_status')->default('unknown');
            $table->string('canonical_status')->default('unknown');
            $table->unsignedSmallInteger('internal_link_suggestions_count')->default(0);
            $table->unsignedSmallInteger('redirect_opportunities_count')->default(0);
            $table->string('search_console_status')->default('unknown');
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'site_id', 'language_id'], 'page_seo_snapshot_unique_context');
            $table->index(['site_id', 'language_id', 'score']);
            $table->index(['site_id', 'critical_count', 'warning_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_seo_snapshots');
    }
};
```

- [ ] **Step 4: Add snapshot status enum**

Create `packages/seo-suite/src/Enums/SeoSnapshotStatusEnum.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

enum SeoSnapshotStatusEnum: string
{
    case Unknown = 'unknown';
    case Missing = 'missing';
    case Warning = 'warning';
    case Passed = 'passed';
    case Declining = 'declining';
}
```

- [ ] **Step 5: Add the model**

Create `packages/seo-suite/src/Models/PageSeoSnapshot.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Models;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSeoSnapshot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'issue_keys' => 'array',
        'passed_check_keys' => 'array',
        'computed_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
```

- [ ] **Step 6: Add the persistence Action**

Create `packages/seo-suite/src/Actions/PersistPageSeoSnapshotAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Data\PageSeoReportData;
use Capell\SeoSuite\Data\SeoIssueData;
use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;
use Capell\SeoSuite\Enums\SeoSnapshotStatusEnum;
use Capell\SeoSuite\Models\PageSeoSnapshot;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static PageSeoSnapshot run(Page $page, Site $site, Language $language, PageSeoReportData $report)
 */
final class PersistPageSeoSnapshotAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language, PageSeoReportData $report): PageSeoSnapshot
    {
        $issues = collect($report->issues)
            ->filter(fn (mixed $issue): bool => $issue instanceof SeoIssueData);

        $passedCheckKeys = collect($report->passedChecks)
            ->map(fn (mixed $check): ?string => $this->checkKeyValue($check))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return PageSeoSnapshot::query()->updateOrCreate(
            [
                'page_id' => $page->getKey(),
                'site_id' => $site->getKey(),
                'language_id' => $language->getKey(),
            ],
            [
                'score' => $report->score,
                'critical_count' => $this->severityCount($issues, SeoIssueSeverityEnum::Critical),
                'warning_count' => $this->severityCount($issues, SeoIssueSeverityEnum::Warning),
                'notice_count' => $this->severityCount($issues, SeoIssueSeverityEnum::Notice),
                'passed_count' => count($passedCheckKeys),
                'issue_keys' => $issues
                    ->map(fn (SeoIssueData $issue): string => $issue->key->value)
                    ->unique()
                    ->values()
                    ->all(),
                'passed_check_keys' => $passedCheckKeys,
                'schema_status' => $report->schemaDashboardReports === []
                    ? SeoSnapshotStatusEnum::Missing->value
                    : SeoSnapshotStatusEnum::Passed->value,
                'robots_status' => $issues->contains(fn (SeoIssueData $issue): bool => $issue->key === SeoCheckKeyEnum::Robots)
                    ? SeoSnapshotStatusEnum::Warning->value
                    : SeoSnapshotStatusEnum::Passed->value,
                'canonical_status' => $issues->contains(fn (SeoIssueData $issue): bool => $issue->key === SeoCheckKeyEnum::Canonical)
                    ? SeoSnapshotStatusEnum::Warning->value
                    : SeoSnapshotStatusEnum::Passed->value,
                'internal_link_suggestions_count' => count($report->internalLinkSuggestions),
                'redirect_opportunities_count' => count($report->redirectOpportunities),
                'search_console_status' => $issues->contains(fn (SeoIssueData $issue): bool => $issue->key === SeoCheckKeyEnum::SearchConsole)
                    ? SeoSnapshotStatusEnum::Warning->value
                    : SeoSnapshotStatusEnum::Unknown->value,
                'computed_at' => now(),
            ],
        );
    }

    private function severityCount(Collection $issues, SeoIssueSeverityEnum $severity): int
    {
        return $issues
            ->filter(fn (SeoIssueData $issue): bool => $issue->severity === $severity)
            ->count();
    }

    private function checkKeyValue(mixed $check): ?string
    {
        if ($check instanceof SeoCheckKeyEnum) {
            return $check->value;
        }

        if (is_string($check) && trim($check) !== '') {
            return trim($check);
        }

        return null;
    }
}
```

- [ ] **Step 7: Run the snapshot test**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add packages/seo-suite/database/migrations/create_page_seo_snapshots_table.php packages/seo-suite/src/Models/PageSeoSnapshot.php packages/seo-suite/src/Enums/SeoSnapshotStatusEnum.php packages/seo-suite/src/Actions/PersistPageSeoSnapshotAction.php packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php
git commit -m "feat: add page seo snapshots"
```

## Task 2: Refresh Page and Site SEO Snapshots

**Files:**

- Create: `packages/seo-suite/src/Actions/RefreshPageSeoSnapshotAction.php`
- Create: `packages/seo-suite/src/Actions/RefreshSiteSeoSnapshotsAction.php`
- Modify: `packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php`

- [ ] **Step 1: Add failing refresh tests**

Append to `PageSeoSnapshotActionTest.php`:

```php
use Capell\SeoSuite\Actions\RefreshPageSeoSnapshotAction;
use Capell\SeoSuite\Actions\RefreshSiteSeoSnapshotsAction;

it('refreshes a single page seo snapshot from the canonical report action', function (): void {
    $language = LanguageFactory::new()->create(['code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()
        ->site($site)
        ->withTranslations($language, ['meta' => []])
        ->create();

    $snapshot = RefreshPageSeoSnapshotAction::run($page, $site, $language);

    expect($snapshot->page_id)->toBe($page->id)
        ->and($snapshot->site_id)->toBe($site->id)
        ->and($snapshot->language_id)->toBe($language->id)
        ->and($snapshot->critical_count)->toBeGreaterThan(0);
});

it('refreshes seo snapshots for every page in a site', function (): void {
    $language = LanguageFactory::new()->create(['code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    PageFactory::new()->count(3)->site($site)->withTranslations($language, ['meta' => []])->create();

    $result = RefreshSiteSeoSnapshotsAction::run($site, $language, 2);

    expect($result)->toBe(['refreshed' => 3])
        ->and(PageSeoSnapshot::query()->where('site_id', $site->id)->count())->toBe(3);
});
```

- [ ] **Step 2: Run the refresh tests**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php
```

Expected: FAIL because refresh Actions do not exist.

- [ ] **Step 3: Add single-page refresh Action**

Create `packages/seo-suite/src/Actions/RefreshPageSeoSnapshotAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Models\PageSeoSnapshot;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static PageSeoSnapshot run(Page $page, Site $site, Language $language)
 */
final class RefreshPageSeoSnapshotAction
{
    use AsAction;

    public function handle(Page $page, Site $site, Language $language): PageSeoSnapshot
    {
        $report = BuildPageSeoReportAction::run($page, $site, $language);

        return PersistPageSeoSnapshotAction::run($page, $site, $language, $report);
    }
}
```

- [ ] **Step 4: Add site refresh Action**

Create `packages/seo-suite/src/Actions/RefreshSiteSeoSnapshotsAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static array{refreshed:int} run(Site $site, Language $language, int $chunkSize = 100)
 */
final class RefreshSiteSeoSnapshotsAction
{
    use AsAction;

    /**
     * @return array{refreshed:int}
     */
    public function handle(Site $site, Language $language, int $chunkSize = 100): array
    {
        $refreshed = 0;

        Page::query()
            ->where('site_id', $site->getKey())
            ->with([
                'site.language',
                'translation.language',
                'translations.language',
                'pageUrl.siteDomain',
            ])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($pages) use ($site, $language, &$refreshed): void {
                foreach ($pages as $page) {
                    if (! $page instanceof Page) {
                        continue;
                    }

                    RefreshPageSeoSnapshotAction::run($page, $site, $language);
                    $refreshed++;
                }
            });

        return ['refreshed' => $refreshed];
    }
}
```

- [ ] **Step 5: Run the refresh tests**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/seo-suite/src/Actions/RefreshPageSeoSnapshotAction.php packages/seo-suite/src/Actions/RefreshSiteSeoSnapshotsAction.php packages/seo-suite/tests/Feature/Actions/PageSeoSnapshotActionTest.php
git commit -m "feat: refresh seo snapshots"
```

## Task 3: Make the SEO Audit Site-Wide and Snapshot-Backed

**Files:**

- Modify: `packages/seo-suite/src/Actions/DashboardReports/BuildSEOAuditQueryAction.php`
- Modify: `packages/seo-suite/src/Filament/Pages/Tables/SEOAuditTable.php`
- Modify: `packages/seo-suite/tests/Feature/Actions/DashboardReports/BuildSEOAuditQueryActionTest.php`

- [ ] **Step 1: Replace the existing narrow audit test**

Update `BuildSEOAuditQueryActionTest.php` so the first test asserts healthy and unhealthy pages are both included:

```php
it('includes healthy and unhealthy pages in the site wide seo audit', function (): void {
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
        ->toContain($healthyPage->getKey())
        ->toContain($unhealthyPage->getKey());
});
```

- [ ] **Step 2: Add a snapshot filter configuration test**

Append:

```php
it('exposes snapshot backed seo audit filters', function (): void {
    $reflectionClass = new ReflectionClass(SEOAuditTable::class);
    $filters = collect($reflectionClass->getMethod('getTableFilters')->invoke(null));

    expect($filters->map(fn ($filter): string => $filter->getName())->all())
        ->toContain('severity')
        ->toContain('issue_key')
        ->toContain('score_band')
        ->toContain('schema_status')
        ->toContain('robots_status')
        ->toContain('canonical_status')
        ->toContain('has_redirect_opportunities')
        ->toContain('search_console_status')
        ->toContain('snapshot_state');
});
```

- [ ] **Step 3: Run the audit tests**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Actions/DashboardReports/BuildSEOAuditQueryActionTest.php
```

Expected: FAIL because the query excludes healthy pages and table filters are missing.

- [ ] **Step 4: Update the audit query**

Replace `BuildSEOAuditQueryAction::handle()` with a site-wide query:

```php
public function handle(): Builder
{
    $query = Page::query()
        ->with([
            'pageUrl.siteDomain',
            'site.language',
            'translation.language',
            'translations.language',
        ]);

    return SiteScope::applyForCurrentActor($query);
}
```

Do not edit the Core `Page` model. Keep `BuildSEOAuditQueryAction` eager loading the existing page relationships only, and have `SEOAuditTable` query `PageSeoSnapshot` directly through a request-local cache.

- [ ] **Step 5: Replace per-row report columns with snapshot columns**

In `SEOAuditTable`, remove static `$dashboardReports`, `reportFor()`, and expensive report callbacks for score/count/status. Add `getTableColumns()` and `getTableFilters()` protected static methods. Columns should use `snapshotFor(Page $record)` state:

```php
TextColumn::make('seo_snapshot_score')
    ->label(__('capell-seo-suite::generic.seo_panel_score'))
    ->state(fn (Page $record): ?int => self::snapshotFor($record)?->score)
    ->placeholder(__('capell-seo-suite::generic.seo_snapshot_not_scanned'))
    ->sortable(false),
```

Implement `snapshotFor(Page $record): ?PageSeoSnapshot` with a request-local static cache keyed by page ID and preferred language ID. Query `PageSeoSnapshot` directly; do not call `BuildPageSeoReportAction`.

- [ ] **Step 6: Add snapshot filters**

Add filters to `SEOAuditTable::getTableFilters()` using `whereExists` against `page_seo_snapshots`. The `issue_key` filter should use JSON contains:

```php
SelectFilter::make('issue_key')
    ->label(__('capell-seo-suite::generic.seo_audit_issue_category'))
    ->options(SeoCheckKeyEnum::class)
    ->query(fn (Builder $query, array $data): Builder => $query->when(
        $data['value'] ?? null,
        fn (Builder $query, string $issueKey): Builder => $query->whereExists(function ($subquery) use ($issueKey): void {
            $subquery
                ->selectRaw('1')
                ->from('page_seo_snapshots')
                ->whereColumn('page_seo_snapshots.page_id', 'pages.id')
                ->whereJsonContains('page_seo_snapshots.issue_keys', $issueKey);
        }),
    ));
```

Use the same pattern for severity counts, score bands, statuses, redirect opportunity count, Search Console status, and stale/missing snapshot state.

- [ ] **Step 7: Run the audit tests**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Actions/DashboardReports/BuildSEOAuditQueryActionTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add packages/seo-suite/src/Actions/DashboardReports/BuildSEOAuditQueryAction.php packages/seo-suite/src/Filament/Pages/Tables/SEOAuditTable.php packages/seo-suite/tests/Feature/Actions/DashboardReports/BuildSEOAuditQueryActionTest.php
git commit -m "feat: make seo audit snapshot backed"
```

## Task 4: Add Report Grouping Helpers and Rich PageSeoPanel Sections

**Files:**

- Modify: `packages/seo-suite/src/Data/PageSeoReportData.php`
- Modify: `packages/seo-suite/src/Filament/Components/Forms/Page/PageSeoPanel.php`
- Modify: `packages/seo-suite/resources/views/filament/components/page-seo-panel.blade.php`
- Modify: `packages/seo-suite/resources/lang/en/generic.php`
- Modify: `packages/seo-suite/tests/Feature/Filament/PageSeoPanelTest.php`

- [ ] **Step 1: Add failing helper tests**

Append to `PageSeoPanelTest.php`:

```php
use Capell\SeoSuite\Data\PageSeoReportData;
use Capell\SeoSuite\Data\SeoIssueData;
use Capell\SeoSuite\Data\SeoPreviewData;
use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;

it('groups seo report data for panel sections', function (): void {
    $report = new PageSeoReportData(
        score: 72,
        searchPreview: new SeoPreviewData(title: 'Title', description: 'Description', url: '/page'),
        socialPreview: new SeoPreviewData(title: 'Social', description: 'Social description', url: '/page'),
        issues: [
            new SeoIssueData(SeoCheckKeyEnum::MetaTitle, SeoIssueSeverityEnum::Critical, 'Missing title.'),
            new SeoIssueData(SeoCheckKeyEnum::Schema, SeoIssueSeverityEnum::Warning, 'Missing schema.'),
            new SeoIssueData(SeoCheckKeyEnum::InternalLinks, SeoIssueSeverityEnum::Notice, 'Add links.'),
        ],
        passedChecks: [SeoCheckKeyEnum::MetaDescription],
    );

    expect($report->issuesBySeverity(SeoIssueSeverityEnum::Critical))->toHaveCount(1)
        ->and($report->issuesForKey(SeoCheckKeyEnum::Schema))->toHaveCount(1)
        ->and($report->passedCheckValues())->toBe([SeoCheckKeyEnum::MetaDescription->value])
        ->and($report->hasIssuesForKey(SeoCheckKeyEnum::InternalLinks))->toBeTrue();
});
```

- [ ] **Step 2: Run the panel tests**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Filament/PageSeoPanelTest.php
```

Expected: FAIL because grouping helpers do not exist.

- [ ] **Step 3: Add helpers to `PageSeoReportData`**

Add methods:

```php
/**
 * @return list<SeoIssueData>
 */
public function issuesBySeverity(SeoIssueSeverityEnum $severity): array
{
    return array_values(array_filter(
        $this->issues,
        fn (SeoIssueData $issue): bool => $issue->severity === $severity,
    ));
}

/**
 * @return list<SeoIssueData>
 */
public function issuesForKey(SeoCheckKeyEnum $key): array
{
    return array_values(array_filter(
        $this->issues,
        fn (SeoIssueData $issue): bool => $issue->key === $key,
    ));
}

public function hasIssuesForKey(SeoCheckKeyEnum $key): bool
{
    return $this->issuesForKey($key) !== [];
}

/**
 * @return list<string>
 */
public function passedCheckValues(): array
{
    return array_values(array_filter(array_map(
        fn (mixed $check): ?string => $check instanceof SeoCheckKeyEnum ? $check->value : (is_string($check) ? $check : null),
        $this->passedChecks,
    )));
}
```

Import `SeoCheckKeyEnum`.

- [ ] **Step 4: Update `PageSeoPanel::reportViewData()`**

Return section-ready data:

```php
return [
    'report' => $report,
    'hasReport' => $report instanceof PageSeoReportData,
    'overviewIssues' => $report instanceof PageSeoReportData ? [
        'critical' => $report->issuesBySeverity(SeoIssueSeverityEnum::Critical),
        'warning' => $report->issuesBySeverity(SeoIssueSeverityEnum::Warning),
        'notice' => $report->issuesBySeverity(SeoIssueSeverityEnum::Notice),
    ] : [],
    'linkIssues' => $report instanceof PageSeoReportData ? $report->issuesForKey(SeoCheckKeyEnum::InternalLinks) : [],
    'schemaIssues' => $report instanceof PageSeoReportData ? $report->issuesForKey(SeoCheckKeyEnum::Schema) : [],
    'searchConsoleIssues' => $report instanceof PageSeoReportData ? $report->issuesForKey(SeoCheckKeyEnum::SearchConsole) : [],
    'robotsIssues' => $report instanceof PageSeoReportData ? array_merge(
        $report->issuesForKey(SeoCheckKeyEnum::Robots),
        $report->issuesForKey(SeoCheckKeyEnum::Canonical),
    ) : [],
    'passedCheckValues' => $report instanceof PageSeoReportData ? $report->passedCheckValues() : [],
];
```

- [ ] **Step 5: Replace the panel Blade layout**

Update `page-seo-panel.blade.php` to render section headings and grouped lists. Keep it compact and avoid nested cards. Use top-level `div` sections with borders:

```blade
<div
    class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
>
    @if (! $hasReport)
        <div class="text-sm text-gray-600 dark:text-gray-300">
            {{ __('capell-seo-suite::generic.seo_panel_empty_state') }}
        </div>
    @else
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-sm font-medium text-gray-950 dark:text-white">
                    {{ __('capell-seo-suite::generic.seo_panel_overview') }}
                </div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('capell-seo-suite::generic.seo_panel_passed_checks', ['count' => count($passedCheckValues)]) }}
                </div>
            </div>
            <div class="flex items-start gap-3">
                {{ $schemaComponent->getAction('ai_content_brief') }}
                <div
                    class="rounded-md bg-gray-50 px-3 py-2 text-right dark:bg-gray-800"
                >
                    <div
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-seo-suite::generic.seo_panel_score') }}
                    </div>
                    <div
                        class="text-2xl font-semibold text-gray-950 dark:text-white"
                    >
                        {{ $report->score }}
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            @include('capell-seo-suite::filament.components.page-seo-panel-section', ['title' => __('capell-seo-suite::generic.seo_panel_links'), 'issues' => $linkIssues])
            @include('capell-seo-suite::filament.components.page-seo-panel-section', ['title' => __('capell-seo-suite::generic.seo_panel_schema'), 'issues' => $schemaIssues])
            @include('capell-seo-suite::filament.components.page-seo-panel-section', ['title' => __('capell-seo-suite::generic.seo_panel_search_console'), 'issues' => $searchConsoleIssues])
            @include('capell-seo-suite::filament.components.page-seo-panel-section', ['title' => __('capell-seo-suite::generic.seo_panel_robots_canonical'), 'issues' => $robotsIssues])
        </div>
    @endif
</div>
```

Inline the repeated section block once per section in `page-seo-panel.blade.php` to avoid adding another Blade file. The implementation must show links, schema, Search Console, robots/canonical, and passed checks as visible sections.

- [ ] **Step 6: Add language strings**

Add keys to `resources/lang/en/generic.php`:

```php
'seo_panel_overview' => 'SEO overview',
'seo_panel_links' => 'Links',
'seo_panel_schema' => 'Schema',
'seo_panel_search_console' => 'Search Console',
'seo_panel_robots_canonical' => 'Robots and canonical',
'seo_panel_passed_checks_heading' => 'Passed checks',
'seo_panel_section_clear' => 'No issues found for this section.',
'seo_snapshot_not_scanned' => 'Not scanned',
'seo_audit_issue_category' => 'Issue category',
```

- [ ] **Step 7: Run the panel tests**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Filament/PageSeoPanelTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add packages/seo-suite/src/Data/PageSeoReportData.php packages/seo-suite/src/Filament/Components/Forms/Page/PageSeoPanel.php packages/seo-suite/resources/views/filament/components/page-seo-panel.blade.php packages/seo-suite/resources/lang/en/generic.php packages/seo-suite/tests/Feature/Filament/PageSeoPanelTest.php
git commit -m "feat: expand page seo panel sections"
```

## Task 5: Add Redirect Create Defaults Through Redirects Package

**Files:**

- Create: `packages/redirects/src/Actions/BuildRedirectCreateUrlAction.php`
- Modify: `packages/redirects/src/Filament/Resources/Redirects/Pages/ManageRedirects.php`
- Modify: `packages/seo-suite/src/Filament/Actions/CreateRedirectFromBrokenLinkAction.php`
- Test: `packages/redirects/tests/Integration/Actions/BuildRedirectCreateUrlActionTest.php`

- [ ] **Step 1: Write the failing Redirects Action test**

Create `packages/redirects/tests/Integration/Actions/BuildRedirectCreateUrlActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Redirects\Actions\BuildRedirectCreateUrlAction;

it('builds a redirects manager url with create defaults', function (): void {
    $url = BuildRedirectCreateUrlAction::run(
        sourceUrl: '/missing-page',
        targetUrl: '/replacement-page',
        siteId: 10,
        languageId: 20,
        statusCode: RedirectStatusCodeEnum::Permanent,
    );

    expect($url)->toContain('create_redirect=1')
        ->toContain('url=%2Fmissing-page')
        ->toContain('target_url=%2Freplacement-page')
        ->toContain('site_id=10')
        ->toContain('language_id=20')
        ->toContain('status_code=301');
});
```

- [ ] **Step 2: Run the failing test**

Run:

```bash
vendor/bin/pest packages/redirects/tests/Integration/Actions/BuildRedirectCreateUrlActionTest.php
```

Expected: FAIL because the Action does not exist.

- [ ] **Step 3: Add `BuildRedirectCreateUrlAction`**

Create `packages/redirects/src/Actions/BuildRedirectCreateUrlAction.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Redirects\Actions;

use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Redirects\Filament\Resources\Redirects\RedirectResource;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static string run(?string $sourceUrl = null, ?string $targetUrl = null, ?int $siteId = null, ?int $languageId = null, RedirectStatusCodeEnum $statusCode = RedirectStatusCodeEnum::Permanent)
 */
final class BuildRedirectCreateUrlAction
{
    use AsAction;

    public function handle(
        ?string $sourceUrl = null,
        ?string $targetUrl = null,
        ?int $siteId = null,
        ?int $languageId = null,
        RedirectStatusCodeEnum $statusCode = RedirectStatusCodeEnum::Permanent,
    ): string {
        return RedirectResource::getUrl('index', [
            'create_redirect' => 1,
            'url' => $sourceUrl,
            'target_url' => $targetUrl,
            'site_id' => $siteId,
            'language_id' => $languageId,
            'status_code' => $statusCode->value,
        ]);
    }
}
```

- [ ] **Step 4: Mount create action from query defaults**

In `ManageRedirects`, add `mount()`:

```php
public function mount(): void
{
    parent::mount();

    if (request()->boolean('create_redirect')) {
        $this->mountAction('create', [
            'site_id' => request()->integer('site_id') ?: null,
            'language_id' => request()->integer('language_id') ?: null,
            'url' => request()->query('url'),
            'target_url' => request()->query('target_url'),
            'status_code' => request()->integer('status_code') ?: RedirectStatusCodeEnum::Permanent->value,
        ]);
    }
}
```

Import `Capell\Core\Enums\RedirectStatusCodeEnum`. Use Filament's `mountAction('create', $arguments)` on this `ManageRecords` page; if the method name differs during implementation, stop and inspect the local `CreateAction` wrapper before changing approach.

- [ ] **Step 5: Update SEO Suite broken-link redirect action**

In `CreateRedirectFromBrokenLinkAction`, replace the generic resource URL with `BuildRedirectCreateUrlAction::run(...)`. Resolve defaults from `$record`:

```php
->url(fn (BrokenLink $record): ?string => $this->redirectCreateUrl($record));
```

Use a helper:

```php
private function redirectCreateUrl(BrokenLink $record): ?string
{
    if (! $this->redirectsAreInstalled()) {
        return null;
    }

    $record->loadMissing(['page.translations', 'page.pageUrls']);
    $sourceUrl = $this->normalisedSourceUrl($record->target_url);
    $page = $record->page;

    return BuildRedirectCreateUrlAction::run(
        sourceUrl: $sourceUrl,
        targetUrl: null,
        siteId: $page?->site_id,
        languageId: $page?->pageUrls->first()?->language_id ?? $page?->translations->first()?->language_id,
        statusCode: RedirectStatusCodeEnum::Permanent,
    );
}
```

Normalize absolute same-site paths with `parse_url($url, PHP_URL_PATH)` and return `null` for unsafe non-path values.

- [ ] **Step 6: Run redirect default tests**

Run:

```bash
vendor/bin/pest packages/redirects/tests/Integration/Actions/BuildRedirectCreateUrlActionTest.php packages/seo-suite/tests/Integration/Actions/CreateRedirectForBrokenLinkActionTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/redirects/src/Actions/BuildRedirectCreateUrlAction.php packages/redirects/src/Filament/Resources/Redirects/Pages/ManageRedirects.php packages/seo-suite/src/Filament/Actions/CreateRedirectFromBrokenLinkAction.php packages/redirects/tests/Integration/Actions/BuildRedirectCreateUrlActionTest.php
git commit -m "feat: prefill redirects from seo broken links"
```

## Task 6: Cache Redirect Health Instead of Validating Per Row

**Files:**

- Create: `packages/redirects/database/migrations/create_redirect_health_snapshots_table.php`
- Create: `packages/redirects/src/Models/RedirectHealthSnapshot.php`
- Create: `packages/redirects/src/Actions/RefreshRedirectHealthSnapshotAction.php`
- Create: `packages/redirects/src/Actions/RefreshRedirectHealthSnapshotsAction.php`
- Modify: `packages/redirects/src/Filament/Resources/Redirects/Tables/RedirectsTable.php`
- Modify: `packages/redirects/tests/Integration/Filament/RedirectsTableSEOColumnsTest.php`
- Test: `packages/redirects/tests/Integration/Actions/RedirectHealthSnapshotActionTest.php`

- [ ] **Step 1: Write redirect health tests**

Create `packages/redirects/tests/Integration/Actions/RedirectHealthSnapshotActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\Redirects\Actions\RefreshRedirectHealthSnapshotAction;
use Capell\Redirects\Models\RedirectHealthSnapshot;

it('stores redirect health warnings without rendering the table', function (): void {
    $language = LanguageFactory::new()->create(['code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $targetRedirect = PageUrl::factory()->site($site)->language($language)->state([
        'url' => '/next',
        'target_url' => '/final',
        'type' => UrlTypeEnum::Redirect,
        'status_code' => RedirectStatusCodeEnum::Permanent,
        'status' => true,
    ])->create();
    $redirect = PageUrl::factory()->site($site)->language($language)->state([
        'url' => '/old',
        'target_url' => '/next',
        'type' => UrlTypeEnum::Redirect,
        'status_code' => RedirectStatusCodeEnum::Permanent,
        'status' => true,
    ])->create();

    $snapshot = RefreshRedirectHealthSnapshotAction::run($redirect);

    expect($snapshot)->toBeInstanceOf(RedirectHealthSnapshot::class)
        ->and($snapshot->page_url_id)->toBe($redirect->id)
        ->and($snapshot->has_chain)->toBeTrue()
        ->and($snapshot->has_loop)->toBeFalse()
        ->and($snapshot->warning_count)->toBeGreaterThan(0)
        ->and($targetRedirect->exists)->toBeTrue();
});
```

- [ ] **Step 2: Run the failing health tests**

Run:

```bash
vendor/bin/pest packages/redirects/tests/Integration/Actions/RedirectHealthSnapshotActionTest.php
```

Expected: FAIL because redirect health storage does not exist.

- [ ] **Step 3: Add migration and model**

Create `create_redirect_health_snapshots_table.php` with:

```php
Schema::create('redirect_health_snapshots', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('page_url_id')->constrained('page_urls')->cascadeOnDelete();
    $table->string('source_url');
    $table->string('target_url')->nullable();
    $table->boolean('has_chain')->default(false);
    $table->boolean('has_loop')->default(false);
    $table->unsignedSmallInteger('warning_count')->default(0);
    $table->unsignedSmallInteger('error_count')->default(0);
    $table->timestamp('computed_at')->nullable();
    $table->timestamps();

    $table->unique('page_url_id');
    $table->index(['has_chain', 'has_loop']);
});
```

Create `RedirectHealthSnapshot` model with guarded empty, boolean casts, datetime cast, and `pageUrl()` belongs-to relation.

- [ ] **Step 4: Add refresh Actions**

`RefreshRedirectHealthSnapshotAction` should call `ValidateRedirectAction::run(...)` once, persist counts, and derive booleans from translated warning/error messages:

```php
$result = ValidateRedirectAction::run(
    sourceUrl: $redirect->url,
    targetUrl: (string) $redirect->target_url,
    siteId: (int) $redirect->site_id,
    languageId: (int) $redirect->language_id,
    excludeId: (int) $redirect->id,
    statusCode: $redirect->status_code?->value,
    validateDuplicateSource: false,
);
```

Set `has_chain` when warnings are not empty. Set `has_loop` when errors contain `__('redirects::message.redirect_loop_detected')`. Keep this contained in `RefreshRedirectHealthSnapshotAction`; do not duplicate redirect traversal in the table.

`RefreshRedirectHealthSnapshotsAction` should chunk active redirect `PageUrl` rows and return `['refreshed' => $count]`.

- [ ] **Step 5: Update `RedirectsTable`**

Remove `ValidateRedirectAction` from imports and remove `chainWarningState()` validation. Read from `RedirectHealthSnapshot`:

```php
TextColumn::make('redirectHealthSnapshot.has_chain')
    ->label(__('redirects::table.chain_warning'))
    ->formatStateUsing(fn (?bool $state): string => $state === true
        ? __('redirects::table.chain_warning_detected')
        : __('redirects::table.chain_warning_none'))
    ->badge()
    ->color(fn (?bool $state): string => $state === true ? 'warning' : 'gray')
```

Do not edit the Core `PageUrl` model. Use a direct lookup cache in `RedirectsTable` that queries `RedirectHealthSnapshot` by `page_url_id`; never call `ValidateRedirectAction` during render.

- [ ] **Step 6: Strengthen table test**

In `RedirectsTableSEOColumnsTest`, add:

```php
$reflection = new ReflectionClass(RedirectsTable::class);
expect(collect($reflection->getMethods())->map(fn (ReflectionMethod $method): string => $method->getName())->all())
    ->not->toContain('chainWarningState');
```

- [ ] **Step 7: Run redirects tests**

Run:

```bash
vendor/bin/pest packages/redirects/tests/Integration/Actions/RedirectHealthSnapshotActionTest.php packages/redirects/tests/Integration/Filament/RedirectsTableSEOColumnsTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add packages/redirects/database/migrations/create_redirect_health_snapshots_table.php packages/redirects/src/Models/RedirectHealthSnapshot.php packages/redirects/src/Actions/RefreshRedirectHealthSnapshotAction.php packages/redirects/src/Actions/RefreshRedirectHealthSnapshotsAction.php packages/redirects/src/Filament/Resources/Redirects/Tables/RedirectsTable.php packages/redirects/tests/Integration/Actions/RedirectHealthSnapshotActionTest.php packages/redirects/tests/Integration/Filament/RedirectsTableSEOColumnsTest.php
git commit -m "feat: cache redirect health warnings"
```

## Task 7: Store Search Console URL Metrics and Declining Pages

**Files:**

- Create: `packages/seo-suite/database/migrations/create_search_console_url_metrics_table.php`
- Create: `packages/seo-suite/src/Models/SearchConsoleUrlMetric.php`
- Create: `packages/seo-suite/src/Actions/PersistSearchConsoleUrlMetricAction.php`
- Modify: `packages/seo-suite/src/Support/SearchConsole/GoogleSearchConsoleClient.php`
- Modify: `packages/seo-suite/src/Actions/SyncSearchConsoleInsightsAction.php`
- Test: `packages/seo-suite/tests/Feature/Actions/SyncSearchConsoleInsightsActionTest.php`

- [ ] **Step 1: Write Search Console sync tests**

Create `packages/seo-suite/tests/Feature/Actions/SyncSearchConsoleInsightsActionTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\SiteFactory;
use Capell\SeoSuite\Actions\PersistSearchConsoleUrlMetricAction;
use Capell\SeoSuite\Actions\SyncSearchConsoleInsightsAction;
use Capell\SeoSuite\Contracts\SearchConsoleClientInterface;
use Capell\SeoSuite\Models\SearchConsoleUrlMetric;

it('returns not configured without writing search console metrics', function (): void {
    $site = SiteFactory::new()->create();

    app()->bind(SearchConsoleClientInterface::class, fn (): object => new class implements SearchConsoleClientInterface {
        public function isConfigured(): bool { return false; }
        public function pageInsights(string $url): array { return []; }
        public function decliningPages(int $siteId, int $limit = 10): array { return []; }
    });

    $result = SyncSearchConsoleInsightsAction::run((int) $site->id);

    expect($result)->toBe(['synced' => 0, 'configured' => false, 'pages' => []])
        ->and(SearchConsoleUrlMetric::query()->count())->toBe(0);
});

it('dashboard-dashboard_reports declining pages from stored search console metrics', function (): void {
    $site = SiteFactory::new()->create();

    PersistSearchConsoleUrlMetricAction::run(
        siteId: (int) $site->id,
        url: 'https://example.com/a',
        windowStart: now()->subDays(28),
        windowEnd: now(),
        clicks: 10,
        impressions: 100,
        ctr: 0.10,
        averagePosition: 4.2,
        previousClicks: 30,
        previousImpressions: 180,
        previousCtr: 0.16,
        previousAveragePosition: 3.1,
    );

    $metric = SearchConsoleUrlMetric::decliningPages((int) $site->id, 10)->first();

    expect($metric?->url)->toBe('https://example.com/a')
        ->and($metric?->click_delta)->toBe(-20);
});
```

- [ ] **Step 2: Run failing Search Console tests**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Actions/SyncSearchConsoleInsightsActionTest.php
```

Expected: FAIL because metric storage does not exist.

- [ ] **Step 3: Add migration and model**

Create `search_console_url_metrics` with the fields from the spec and indexes on `site_id`, `url`, `window_start`, `window_end`, and `click_delta`.

Create `SearchConsoleUrlMetric` with numeric casts and a scope:

```php
public function scopeDecliningPages(Builder $query, int $siteId, int $limit = 10): Builder
{
    return $query
        ->where('site_id', $siteId)
        ->where('click_delta', '<', 0)
        ->orderBy('click_delta')
        ->limit($limit);
}
```

- [ ] **Step 4: Add persist Action**

Create `PersistSearchConsoleUrlMetricAction` that computes deltas and upserts by `site_id`, `url`, `window_start`, and `window_end`.

- [ ] **Step 5: Implement `GoogleSearchConsoleClient::decliningPages()`**

Use `SearchConsoleUrlMetric::query()->decliningPages($siteId, $limit)->get()->all()`. Return arrays consistently with the current interface tests:

```php
return SearchConsoleUrlMetric::query()
    ->decliningPages($siteId, $limit)
    ->get()
    ->map(fn (SearchConsoleUrlMetric $metric): array => [
        'url' => $metric->url,
        'clicks' => $metric->clicks,
        'previous_clicks' => $metric->previous_clicks,
        'click_delta' => $metric->click_delta,
    ])
    ->all();
```

- [ ] **Step 6: Keep sync honest**

Update `SyncSearchConsoleInsightsAction` so it returns `configured: false` without writing when the client is not configured, and returns stored declining pages when configured. Return `synced: count($pages)` where pages come from stored metrics.

- [ ] **Step 7: Run Search Console tests**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests/Feature/Actions/SyncSearchConsoleInsightsActionTest.php packages/seo-suite/tests/Unit/SearchConsole
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add packages/seo-suite/database/migrations/create_search_console_url_metrics_table.php packages/seo-suite/src/Models/SearchConsoleUrlMetric.php packages/seo-suite/src/Actions/PersistSearchConsoleUrlMetricAction.php packages/seo-suite/src/Support/SearchConsole/GoogleSearchConsoleClient.php packages/seo-suite/src/Actions/SyncSearchConsoleInsightsAction.php packages/seo-suite/tests/Feature/Actions/SyncSearchConsoleInsightsActionTest.php packages/seo-suite/tests/Unit/SearchConsole
git commit -m "feat: store search console url metrics"
```

## Task 8: Wire Publish Gates Config

**Files:**

- Modify: `packages/seo-suite/config/capell-seo-suite.php`
- Modify: `packages/seo-suite/src/Support/Publishing/SeoPublishReportProviderAdapter.php`
- Modify: `packages/publishing-studio/src/Checks/SeoMetaCheck.php`
- Modify: `packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php`

- [ ] **Step 1: Add publish gate config tests**

In `SeoMetaCheckTest.php`, add a case where `search_console` is ignored and `meta_title` is blocker:

```php
it('maps seo issue keys through seo tools publish gate config', function (): void {
    config()->set('capell-seo-suite.publish_gates.checks', [
        'meta_title' => 'blocker',
        'search_console' => 'ignored',
    ]);

    app()->instance(SEO_PUBLISH_REPORT_PROVIDER, new class
    {
        public function forWorkspace(Workspace $workspace): array
        {
            return [[
                'page' => ['id' => 1, 'label' => 'home'],
                'issues' => [
                    ['key' => 'meta_title', 'severity' => 'critical', 'message' => 'Missing title.'],
                    ['key' => 'search_console', 'severity' => 'notice', 'message' => 'Low impressions.'],
                ],
            ]];
        }
    });

    $result = (new SeoMetaCheck)->run(Workspace::factory()->create());

    expect($result->severity)->toBe(PublishCheckSeverity::Error)
        ->and($result->messages)->toBe(["Page 'home': Missing title."]);
});
```

- [ ] **Step 2: Run the publish check test**

Run:

```bash
vendor/bin/pest packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php
```

Expected: FAIL because config mapping is not used yet.

- [ ] **Step 3: Add config defaults**

In `capell-seo-suite.php`, import `SeoCheckModeEnum` and add:

```php
'publish_gates' => [
    'checks' => [
        'meta_title' => SeoCheckModeEnum::Blocker->value,
        'meta_description' => SeoCheckModeEnum::Blocker->value,
        'robots' => SeoCheckModeEnum::Blocker->value,
        'canonical' => SeoCheckModeEnum::Warning->value,
        'schema' => SeoCheckModeEnum::Warning->value,
        'internal_links' => SeoCheckModeEnum::Warning->value,
        'social_image' => SeoCheckModeEnum::Warning->value,
        'redirects' => SeoCheckModeEnum::Blocker->value,
        'search_console' => SeoCheckModeEnum::Ignored->value,
    ],
],
```

- [ ] **Step 4: Map issue keys in `SeoMetaCheck`**

In `runSeoSuiteProvider()`, before setting `$hasCriticalIssue` or `$hasWarnIssue`, resolve the mode:

```php
$mode = $this->publishGateMode($issue['key'] ?? null, $severity);

if ($mode === 'ignored') {
    continue;
}

if ($mode === 'blocker') {
    $hasCriticalIssue = true;
} elseif ($mode === 'warning') {
    $hasWarnIssue = true;
}
```

Add:

```php
private function publishGateMode(mixed $key, ?string $severity): string
{
    $keyValue = is_scalar($key) ? (string) $key : '';
    $configured = config('capell-seo-suite.publish_gates.checks.' . $keyValue);

    if (in_array($configured, ['blocker', 'warning', 'ignored'], true)) {
        return $configured;
    }

    return $severity === 'critical' ? 'blocker' : 'warning';
}
```

- [ ] **Step 5: Run publish check tests**

Run:

```bash
vendor/bin/pest packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/seo-suite/config/capell-seo-suite.php packages/publishing-studio/src/Checks/SeoMetaCheck.php packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php
git commit -m "feat: configure seo publish gates"
```

## Task 9: Final Verification and Documentation

**Files:**

- Modify: `packages/seo-suite/docs/seo-intelligence.md`
- Modify: `packages/seo-suite/docs/search-console.md`
- Modify: `packages/redirects/docs/redirects.md`

- [ ] **Step 1: Document the new refresh model**

In `seo-intelligence.md`, add short sections for page SEO snapshots, audit filters, and manual refresh Actions. Include:

```md
SEO snapshots are query projections, not the source SEO report. Use `BuildPageSeoReportAction` for detail and `RefreshPageSeoSnapshotAction` or `RefreshSiteSeoSnapshotsAction` when a filterable admin view needs fresh state.
```

- [ ] **Step 2: Document Search Console metric storage**

In `search-console.md`, replace any wording that says sync is only a boundary action with:

```md
`SyncSearchConsoleInsightsAction` dashboard-dashboard_reports from stored URL metric windows. When credentials are missing it returns `configured: false`; when metrics exist it can report declining pages from `search_console_url_metrics`.
```

- [ ] **Step 3: Document redirect health cache**

In `redirects.md`, add:

```md
Redirect chain and loop badges are read from `redirect_health_snapshots`. Run `RefreshRedirectHealthSnapshotAction` after changing one redirect or `RefreshRedirectHealthSnapshotsAction` after imports.
```

- [ ] **Step 4: Run focused package tests**

Run:

```bash
vendor/bin/pest packages/seo-suite/tests
vendor/bin/pest packages/redirects/tests
vendor/bin/pest packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php
```

Expected: PASS.

- [ ] **Step 5: Run preflight**

Run:

```bash
composer preflight
```

Expected: PASS.

- [ ] **Step 6: Commit docs and final fixes**

```bash
git add packages/seo-suite/docs/seo-intelligence.md packages/seo-suite/docs/search-console.md packages/redirects/docs/redirects.md
git commit -m "docs: explain seo intelligence refreshes"
```

## Implementation Notes

- Do not use `php artisan`; use `vendor/bin/pest`.
- Keep all PHP files strict typed.
- Avoid single-letter variable names, including closures.
- If a relation would require editing a Core model from a package, prefer direct query helpers in the owning package unless the repo already has an established extension pattern.
- Do not run redirect validation, Search Console HTTP requests, or page report builds from table row render callbacks.
- Do not add external Composer packages.

## Final Verification Checklist

- [ ] `vendor/bin/pest packages/seo-suite/tests` passes.
- [ ] `vendor/bin/pest packages/redirects/tests` passes.
- [ ] `vendor/bin/pest packages/publishing-studio/tests/Unit/Checks/SeoMetaCheckTest.php` passes.
- [ ] `composer preflight` passes.
- [ ] No unrelated dirty files were staged.
