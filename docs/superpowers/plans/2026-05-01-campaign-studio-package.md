# CampaignStudio Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `capell-app/campaign-studio` as a marketing package for campaign groups, landing pages, UTM attribution, reusable CTA blocks, conversion goals, campaign-focused LayoutBuilder layouts, and Insights reporting.

**Architecture:** CampaignStudio composes existing Capell packages instead of duplicating them. LayoutBuilder remains the layout and widget engine, FormBuilder remains the lead capture engine, Insights remains the visit/event store, and SEO Suite remains responsible for page metadata. The campaign-studio package owns campaign domain models, admin resources, conversion attribution actions, LayoutBuilder campaign widgets, and reporting widgets.

**Tech Stack:** PHP 8.2, Laravel package tools, Pest, Lorisleiva Actions, Spatie Laravel Data, Filament resources/widgets, Capell Core/Admin/Frontend, LayoutBuilder, FormBuilder, Insights, and optional SEO Suite integration through page schema extenders.

---

## Ground Rules

- Work in `/Users/ben/Sites/packages/capell/capell-packages-4`.
- Preserve the current dirty worktree. Only edit or stage files created or changed for the campaign-studio package.
- Do not run `php artisan`; use `vendor/bin/pest` directly.
- Every PHP file must start with `declare(strict_types=1);`.
- All closures must declare parameter and return types.
- Do not introduce single-letter or cryptic variable names.
- Domain behavior lives in `packages/campaign-studio/src/Actions`.
- Structured input/output lives in `packages/campaign-studio/src/Data`.
- Persisted string values use backed enums in `packages/campaign-studio/src/Enums`.
- User-facing strings use `__('capell-campaign-studio::...')`.
- The package may import LayoutBuilder, FormBuilder, Insights, and SEO Suite classes. Core must not import CampaignStudio classes.

## Scope

Build the first useful version of CampaignStudio:

- Campaign groups with status, date window, budget metadata, and default UTM values.
- Landing page records that link a campaign group to an existing Capell page.
- Conversion goals that can target page views, CTA clicks, form submissions, or custom Insights actions.
- Conversion records that store attributed Insights visit/event data.
- Reusable CTA blocks with buttons, destination URLs, and default UTM tagging.
- Campaign LayoutBuilder widgets and installable campaign layout presets.
- Admin resources for groups, landing pages, CTA blocks, and conversion goals.
- Insights dashboard widgets for conversions, conversion rate, top campaign-studio, and top landing pages.

Leave these out of v1:

- External ad platform imports.
- A/B testing and variant traffic splitting.
- External CRM sync.
- GA4 forwarding.
- Attribution models beyond first-touch and last-touch fields stored on conversion records.

## File Structure

Create these files:

- `packages/campaign-studio/composer.json`: package metadata and provider discovery.
- `packages/campaign-studio/capell.json`: Capell package manifest with dependencies.
- `packages/campaign-studio/config/capell-campaign-studio.php`: table names, UTM keys, conversion cookie, and layout preset settings.
- `packages/campaign-studio/resources/lang/en/package.php`: package description.
- `packages/campaign-studio/resources/lang/en/generic.php`: shared labels.
- `packages/campaign-studio/resources/lang/en/form.php`: form labels.
- `packages/campaign-studio/resources/lang/en/navigation.php`: navigation labels.
- `packages/campaign-studio/resources/lang/en/widgets.php`: dashboard widget labels.
- `packages/campaign-studio/resources/views/components/widget/campaign-hero.blade.php`: campaign hero LayoutBuilder widget view.
- `packages/campaign-studio/resources/views/components/widget/campaign-cta-block.blade.php`: reusable CTA block LayoutBuilder widget view.
- `packages/campaign-studio/resources/views/components/widget/campaign-lead-form.blade.php`: lead form LayoutBuilder widget view.
- `packages/campaign-studio/resources/views/components/tracking/attributes.blade.php`: reusable tracking attributes partial.
- `packages/campaign-studio/src/Providers/CampaignStudioServiceProvider.php`: shared package registration.
- `packages/campaign-studio/src/Providers/AdminServiceProvider.php`: Filament resources and widgets.
- `packages/campaign-studio/src/Providers/FrontendServiceProvider.php`: frontend components and render hooks.
- `packages/campaign-studio/src/Enums/CampaignStatus.php`: campaign lifecycle enum.
- `packages/campaign-studio/src/Enums/ConversionGoalType.php`: conversion goal trigger enum.
- `packages/campaign-studio/src/Enums/AttributionModel.php`: attribution model enum.
- `packages/campaign-studio/src/Enums/ResourceEnum.php`: admin resource enum.
- `packages/campaign-studio/src/Enums/CampaignWidgetComponentEnum.php`: LayoutBuilder widget view enum.
- `packages/campaign-studio/src/Enums/CampaignWidgetConfiguratorEnum.php`: LayoutBuilder widget configurator enum.
- `packages/campaign-studio/src/Data/UtmData.php`: structured UTM values.
- `packages/campaign-studio/src/Data/CampaignCtaActionData.php`: CTA action value object.
- `packages/campaign-studio/src/Data/ConversionAttributionData.php`: conversion attribution snapshot.
- `packages/campaign-studio/src/Data/Dashboard/CampaignConversionSummaryData.php`: campaign dashboard summary.
- `packages/campaign-studio/src/Data/Dashboard/CampaignLandingPageSummaryData.php`: landing page dashboard summary.
- `packages/campaign-studio/src/Models/CampaignGroup.php`: campaign group model.
- `packages/campaign-studio/src/Models/CampaignLandingPage.php`: campaign landing page model.
- `packages/campaign-studio/src/Models/CampaignCtaBlock.php`: reusable CTA block model.
- `packages/campaign-studio/src/Models/CampaignConversionGoal.php`: conversion goal model.
- `packages/campaign-studio/src/Models/CampaignConversion.php`: recorded conversion model.
- `packages/campaign-studio/src/Actions/ResolveCampaignFromUrlAction.php`: resolve campaign from UTM or page URL.
- `packages/campaign-studio/src/Actions/BuildCampaignUrlAction.php`: append missing UTM values to CTA URLs.
- `packages/campaign-studio/src/Actions/BuildConversionAttributionAction.php`: build conversion attribution snapshots.
- `packages/campaign-studio/src/Actions/RecordCampaignConversionAction.php`: record idempotent conversions.
- `packages/campaign-studio/src/Actions/RecordCtaClickConversionAction.php`: record CTA click conversions.
- `packages/campaign-studio/src/Actions/RecordFormSubmissionConversionAction.php`: record form submission conversions.
- `packages/campaign-studio/src/Actions/RecordPageViewConversionAction.php`: record page view conversions.
- `packages/campaign-studio/src/Actions/InstallCampaignLayoutsAction.php`: install LayoutBuilder campaign layouts.
- `packages/campaign-studio/src/Actions/BuildCampaignOverviewStatsAction.php`: dashboard totals.
- `packages/campaign-studio/src/Actions/BuildCampaignConversionFunnelAction.php`: funnel summaries.
- `packages/campaign-studio/src/Actions/BuildTopCampaignStudioQueryAction.php`: top campaign-studio report.
- `packages/campaign-studio/src/Actions/BuildTopLandingPagesQueryAction.php`: top landing pages report.
- `packages/campaign-studio/src/Listeners/RecordFormSubmissionConversion.php`: FormBuilder event listener.
- `packages/campaign-studio/src/Filament/Resources/CampaignGroups/CampaignGroupResource.php`: campaign group resource.
- `packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/CampaignLandingPageResource.php`: landing page resource.
- `packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/CampaignCtaBlockResource.php`: CTA block resource.
- `packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/CampaignConversionGoalResource.php`: conversion goal resource.
- `packages/campaign-studio/src/Filament/Configurators/Widgets/CampaignHeroWidgetConfigurator.php`: campaign hero widget form.
- `packages/campaign-studio/src/Filament/Configurators/Widgets/CampaignCtaBlockWidgetConfigurator.php`: CTA block widget form.
- `packages/campaign-studio/src/Filament/Configurators/Widgets/CampaignLeadFormWidgetConfigurator.php`: lead form widget form.
- `packages/campaign-studio/src/Filament/Extenders/Page/CampaignPageSchemaExtender.php`: page campaign metadata fields.
- `packages/campaign-studio/src/Filament/Widgets/CampaignOverviewStatsWidget.php`: campaign stats dashboard widget.
- `packages/campaign-studio/src/Filament/Widgets/TopCampaignStudioWidget.php`: top campaign-studio dashboard widget.
- `packages/campaign-studio/src/Filament/Widgets/TopLandingPagesWidget.php`: top landing pages dashboard widget.
- `packages/campaign-studio/database/migrations/create_campaign_groups_table.php`: campaign groups table.
- `packages/campaign-studio/database/migrations/create_campaign_landing_pages_table.php`: landing pages table.
- `packages/campaign-studio/database/migrations/create_campaign_cta_blocks_table.php`: CTA blocks table.
- `packages/campaign-studio/database/migrations/create_campaign_conversion_goals_table.php`: conversion goals table.
- `packages/campaign-studio/database/migrations/create_campaign_conversions_table.php`: conversions table.
- `packages/campaign-studio/database/factories/CampaignGroupFactory.php`: group factory.
- `packages/campaign-studio/database/factories/CampaignLandingPageFactory.php`: landing page factory.
- `packages/campaign-studio/database/factories/CampaignCtaBlockFactory.php`: CTA block factory.
- `packages/campaign-studio/database/factories/CampaignConversionGoalFactory.php`: goal factory.
- `packages/campaign-studio/database/factories/CampaignConversionFactory.php`: conversion factory.
- `packages/campaign-studio/tests/CampaignStudioTestCase.php`: package test case.
- `packages/campaign-studio/tests/Pest.php`: Pest setup.
- `packages/campaign-studio/tests/Unit/Providers/CampaignStudioServiceProviderTest.php`: provider smoke tests.
- `packages/campaign-studio/tests/Unit/Actions/ResolveCampaignFromUrlActionTest.php`: campaign URL resolution tests.
- `packages/campaign-studio/tests/Unit/Actions/CampaignReportingActionsTest.php`: reporting action tests.
- `packages/campaign-studio/tests/Integration/Database/CampaignMigrationsTest.php`: migration tests.
- `packages/campaign-studio/tests/Integration/Models/CampaignRelationshipsTest.php`: model relationship tests.
- `packages/campaign-studio/tests/Integration/Actions/RecordCampaignConversionActionTest.php`: conversion action tests.
- `packages/campaign-studio/tests/Integration/Actions/InstallCampaignLayoutsActionTest.php`: layout installer tests.
- `packages/campaign-studio/tests/Integration/Listeners/FormSubmissionConversionTest.php`: FormBuilder listener tests.
- `packages/campaign-studio/tests/Feature/Filament/CampaignResourcesTest.php`: admin resource tests.
- `packages/campaign-studio/tests/Feature/Filament/CampaignInsightsWidgetsTest.php`: dashboard widget tests.
- `packages/campaign-studio/tests/Feature/LayoutBuilder/CampaignWidgetsTest.php`: LayoutBuilder widget tests.
- `packages/campaign-studio/tests/Feature/PageSchema/CampaignPageSchemaExtenderTest.php`: page schema extender tests.

Modify these files:

- `composer.json`: add `Capell\\CampaignStudio\\` and `Capell\\CampaignStudio\\Database\\Factories\\` autoload entries.
- `tests/Packages/PackagesTestCase.php`: include CampaignStudio in cross-package boot tests once package registration is implemented.
- `tests/Packages/Integration/CrossPackageBootTest.php`: assert CampaignStudio provider is discoverable.

## Task 1: Package Skeleton And Registration

**Files:**

- Create: `packages/campaign-studio/composer.json`
- Create: `packages/campaign-studio/capell.json`
- Create: `packages/campaign-studio/config/capell-campaign-studio.php`
- Create: `packages/campaign-studio/resources/lang/en/package.php`
- Create: `packages/campaign-studio/resources/lang/en/generic.php`
- Create: `packages/campaign-studio/src/Providers/CampaignStudioServiceProvider.php`
- Create: `packages/campaign-studio/src/Providers/AdminServiceProvider.php`
- Create: `packages/campaign-studio/src/Providers/FrontendServiceProvider.php`
- Create: `packages/campaign-studio/tests/Pest.php`
- Create: `packages/campaign-studio/tests/CampaignStudioTestCase.php`
- Create: `packages/campaign-studio/tests/Unit/Providers/CampaignStudioServiceProviderTest.php`
- Modify: `composer.json`

- [ ] **Step 1: Write the failing provider smoke tests**

Create `packages/campaign-studio/tests/Pest.php`:

```php
<?php

declare(strict_types=1);

use Capell\CampaignStudio\Tests\CampaignStudioTestCase;

uses(CampaignStudioTestCase::class)->in(__DIR__);
```

Create `packages/campaign-studio/tests/Unit/Providers/CampaignStudioServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\CampaignStudio\Providers\CampaignStudioServiceProvider;
use Capell\Core\Facades\CapellCore;

it('registers the campaign-studio package metadata', function (): void {
    $package = CapellCore::getPackage(CampaignStudioServiceProvider::$packageName);

    expect($package->name)->toBe(CampaignStudioServiceProvider::$packageName);
});

it('loads the campaign-studio config', function (): void {
    expect(config('capell-campaign-studio.tables.groups'))->toBe('campaign_groups')
        ->and(config('capell-campaign-studio.conversion_cookie'))->toBe('capell_campaign_visit');
});
```

- [ ] **Step 2: Run the smoke test and verify it fails**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Unit/Providers/CampaignStudioServiceProviderTest.php
```

Expected: FAIL because the campaign-studio provider does not exist yet.

- [ ] **Step 3: Add package metadata and provider classes**

Create `packages/campaign-studio/capell.json`:

```json
{
    "name": "capell-app/campaign-studio",
    "description": "Campaign landing pages, CTA blocks, UTM attribution, conversion goals, and campaign insights.",
    "providers": {
        "shared": [
            "Capell\\CampaignStudio\\Providers\\CampaignStudioServiceProvider"
        ],
        "admin": ["Capell\\CampaignStudio\\Providers\\AdminServiceProvider"],
        "frontend": [
            "Capell\\CampaignStudio\\Providers\\FrontendServiceProvider"
        ]
    },
    "dependencies": [
        "capell-app/layout-builder",
        "capell-app/form-builder",
        "capell-app/insights"
    ],
    "optional": ["capell-app/seo-suite"]
}
```

Create `packages/campaign-studio/config/capell-campaign-studio.php`:

```php
<?php

declare(strict_types=1);

return [
    'conversion_cookie' => 'capell_campaign_visit',
    'utm_keys' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'],
    'tables' => [
        'groups' => 'campaign_groups',
        'landing_pages' => 'campaign_landing_pages',
        'cta_blocks' => 'campaign_cta_blocks',
        'conversion_goals' => 'campaign_conversion_goals',
        'conversions' => 'campaign_conversions',
    ],
    'layout_presets' => [
        'enabled' => true,
    ],
];
```

Create `packages/campaign-studio/src/Providers/CampaignStudioServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Providers;

use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

final class CampaignStudioServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-campaign-studio';

    public static string $packageName = 'capell-app/campaign-studio';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-campaign-studio')
            ->hasTranslations()
            ->hasViews(self::$name)
            ->hasMigrations([
                'create_campaign_groups_table',
                'create_campaign_landing_pages_table',
                'create_campaign_cta_blocks_table',
                'create_campaign_conversion_goals_table',
                'create_campaign_conversions_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this
            ->registerPackageMetadata()
            ->registerPackageAssets()
            ->registerProtectedTables();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => __('capell-campaign-studio::package.description'),
        );

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );

        return $this;
    }

    private function registerProtectedTables(): self
    {
        foreach (config('capell-campaign-studio.tables', []) as $tableName) {
            if (is_string($tableName) && $tableName !== '') {
                CapellCore::registerProtectedTable(fn (): string => $tableName);
            }
        }

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled(self::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(self::$packageName) ?? 'dev';
    }
}
```

- [ ] **Step 4: Add test case and autoload entries**

Create `packages/campaign-studio/tests/CampaignStudioTestCase.php` using the same provider style as Insights, with Admin, Frontend, LayoutBuilder, FormBuilder, Insights, and CampaignStudio providers installed through `CapellCore::forcePackageInstalled(...)`.

Modify root `composer.json` autoload sections:

```json
"Capell\\CampaignStudio\\": "packages/campaign-studio/src",
"Capell\\CampaignStudio\\Database\\Factories\\": "packages/campaign-studio/database/factories"
```

```json
"Capell\\CampaignStudio\\Tests\\": "packages/campaign-studio/tests"
```

- [ ] **Step 5: Verify package discovery**

Run:

```bash
composer dump-autoload
vendor/bin/pest packages/campaign-studio/tests/Unit/Providers/CampaignStudioServiceProviderTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add composer.json packages/campaign-studio
git commit -m "feat: add campaign-studio package skeleton"
```

## Task 2: Campaign Domain Tables, Models, Data, And Enums

**Files:**

- Create: `packages/campaign-studio/database/migrations/create_campaign_groups_table.php`
- Create: `packages/campaign-studio/database/migrations/create_campaign_landing_pages_table.php`
- Create: `packages/campaign-studio/database/migrations/create_campaign_cta_blocks_table.php`
- Create: `packages/campaign-studio/database/migrations/create_campaign_conversion_goals_table.php`
- Create: `packages/campaign-studio/database/migrations/create_campaign_conversions_table.php`
- Create: `packages/campaign-studio/src/Enums/CampaignStatus.php`
- Create: `packages/campaign-studio/src/Enums/ConversionGoalType.php`
- Create: `packages/campaign-studio/src/Enums/AttributionModel.php`
- Create: `packages/campaign-studio/src/Data/UtmData.php`
- Create: `packages/campaign-studio/src/Data/CampaignCtaActionData.php`
- Create: `packages/campaign-studio/src/Data/ConversionAttributionData.php`
- Create: `packages/campaign-studio/src/Models/CampaignGroup.php`
- Create: `packages/campaign-studio/src/Models/CampaignLandingPage.php`
- Create: `packages/campaign-studio/src/Models/CampaignCtaBlock.php`
- Create: `packages/campaign-studio/src/Models/CampaignConversionGoal.php`
- Create: `packages/campaign-studio/src/Models/CampaignConversion.php`
- Create: `packages/campaign-studio/database/factories/CampaignGroupFactory.php`
- Create: `packages/campaign-studio/database/factories/CampaignLandingPageFactory.php`
- Create: `packages/campaign-studio/database/factories/CampaignCtaBlockFactory.php`
- Create: `packages/campaign-studio/database/factories/CampaignConversionGoalFactory.php`
- Create: `packages/campaign-studio/database/factories/CampaignConversionFactory.php`
- Create: `packages/campaign-studio/tests/Integration/Database/CampaignMigrationsTest.php`
- Create: `packages/campaign-studio/tests/Integration/Models/CampaignRelationshipsTest.php`

- [ ] **Step 1: Write failing migration and model relationship tests**

Test the following:

- The five campaign tables exist.
- A campaign group has many landing pages, CTA blocks, conversion goals, and conversions.
- A landing page belongs to a campaign group and a Core page.
- A conversion belongs to a goal and optionally an Insights visit/event.
- JSON casts return `UtmData`, `CampaignCtaActionData` collection, and `ConversionAttributionData`.

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Integration/Database packages/campaign-studio/tests/Integration/Models
```

Expected: FAIL because migrations and models do not exist.

- [ ] **Step 2: Add migrations**

Use table names from `config('capell-campaign-studio.tables.*')`. Include:

- `campaign_groups`: `site_id`, `name`, `slug`, `status`, `starts_at`, `ends_at`, `utm_source`, `utm_medium`, `utm_campaign`, `budget_amount`, `notes`, timestamps, soft deletes.
- `campaign_landing_pages`: `campaign_group_id`, `page_id`, `headline`, `primary_goal_id`, `utm_content`, `utm_term`, `is_primary`, timestamps.
- `campaign_cta_blocks`: `campaign_group_id`, `site_id`, `name`, `key`, `headline`, `body`, `actions`, `default_utm`, `is_active`, timestamps, soft deletes.
- `campaign_conversion_goals`: `campaign_group_id`, `site_id`, `name`, `key`, `type`, `target`, `value_amount`, `is_primary`, `is_active`, timestamps, soft deletes.
- `campaign_conversions`: `campaign_group_id`, `campaign_landing_page_id`, `campaign_conversion_goal_id`, `insights_visit_id`, `insights_event_id`, `site_id`, `language_id`, `attribution`, `converted_at`, timestamps.

Every migration closure must be typed:

```php
Schema::create($tableName, function (Blueprint $table): void {
    $table->id();
});
```

- [ ] **Step 3: Add enums and data classes**

Create `CampaignStatus` with `Draft`, `Scheduled`, `Active`, `Paused`, `Ended`.

Create `ConversionGoalType` with `PageView`, `CtaClick`, `FormSubmission`, `CustomAction`.

Create `AttributionModel` with `FirstTouch`, `LastTouch`.

Create `UtmData`:

```php
<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class UtmData extends Data
{
    public function __construct(
        public ?string $source = null,
        public ?string $medium = null,
        public ?string $campaign = null,
        public ?string $term = null,
        public ?string $content = null,
    ) {}
}
```

- [ ] **Step 4: Add models and factories**

Models must use guarded or fillable consistently with nearby packages. Prefer explicit `$fillable` for CampaignStudio.

Relationships:

- `CampaignGroup::landingPages()`
- `CampaignGroup::ctaBlocks()`
- `CampaignGroup::conversionGoals()`
- `CampaignGroup::conversions()`
- `CampaignLandingPage::campaignGroup()`
- `CampaignLandingPage::page()`
- `CampaignCtaBlock::campaignGroup()`
- `CampaignConversionGoal::campaignGroup()`
- `CampaignConversion::campaignGroup()`
- `CampaignConversion::landingPage()`
- `CampaignConversion::goal()`
- `CampaignConversion::visit()`
- `CampaignConversion::event()`

- [ ] **Step 5: Register models in the service provider**

Add `CapellCore::registerModels([...])` to `CampaignStudioServiceProvider`.

- [ ] **Step 6: Verify**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Integration/Database packages/campaign-studio/tests/Integration/Models
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/campaign-studio
git commit -m "feat: add campaign domain models"
```

## Task 3: Campaign Attribution And Conversion Actions

**Files:**

- Create: `packages/campaign-studio/src/Actions/ResolveCampaignFromUrlAction.php`
- Create: `packages/campaign-studio/src/Actions/BuildConversionAttributionAction.php`
- Create: `packages/campaign-studio/src/Actions/RecordCampaignConversionAction.php`
- Create: `packages/campaign-studio/src/Actions/RecordCtaClickConversionAction.php`
- Create: `packages/campaign-studio/src/Actions/RecordFormSubmissionConversionAction.php`
- Create: `packages/campaign-studio/src/Actions/RecordPageViewConversionAction.php`
- Create: `packages/campaign-studio/src/Listeners/RecordFormSubmissionConversion.php`
- Create: `packages/campaign-studio/tests/Unit/Actions/ResolveCampaignFromUrlActionTest.php`
- Create: `packages/campaign-studio/tests/Integration/Actions/RecordCampaignConversionActionTest.php`
- Create: `packages/campaign-studio/tests/Integration/Listeners/FormSubmissionConversionTest.php`

- [ ] **Step 1: Write failing action tests**

Test:

- `ResolveCampaignFromUrlAction` matches `utm_campaign` to active `CampaignGroup::slug`.
- `ResolveCampaignFromUrlAction` falls back to a configured landing page by page path.
- `RecordCampaignConversionAction` is idempotent for the same goal, visit, and event.
- Form submissions with a matching goal create a conversion.
- Disabled goals do not create conversions.

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Unit/Actions packages/campaign-studio/tests/Integration/Actions packages/campaign-studio/tests/Integration/Listeners
```

Expected: FAIL because actions do not exist.

- [ ] **Step 2: Implement URL resolution**

`ResolveCampaignFromUrlAction::handle(string $url): ?CampaignGroup` should:

- Parse query values using `parse_url()` and `parse_str()`.
- Match `utm_campaign` to `CampaignGroup.slug` first.
- Match URL path to a `CampaignLandingPage` page translation URL second, using the existing page model relationships available in Core.
- Return `null` when no active campaign matches.

- [ ] **Step 3: Implement conversion attribution**

`BuildConversionAttributionAction::handle(?InsightsVisit $visit, ?InsightsEvent $event): ConversionAttributionData` should store:

- landing URL
- referrer URL
- UTM source, medium, campaign, term, content
- event name
- event label
- event location
- first-touch campaign from visit fields
- last-touch campaign from event metadata when available

- [ ] **Step 4: Implement idempotent conversion recording**

`RecordCampaignConversionAction::handle(CampaignConversionGoal $goal, ?InsightsVisit $visit, ?InsightsEvent $event, ?CampaignLandingPage $landingPage = null): ?CampaignConversion` should:

- Return `null` when the goal is inactive.
- Resolve the campaign group from the goal.
- Use `firstOrCreate` on `campaign_conversion_goal_id`, `insights_visit_id`, and `insights_event_id`.
- Store `converted_at` from the event occurrence or `now()->toImmutable()`.
- Store attribution via `BuildConversionAttributionAction::run(...)`.

- [ ] **Step 5: Wire FormBuilder integration**

Listen to `Capell\FormBuilder\Events\FormSubmitted`. For goals with `type = FormSubmission`, match goal `target` against the submitted form handle or form id. Use the submission meta URL to resolve the landing page and visit when possible.

- [ ] **Step 6: Verify**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Unit/Actions packages/campaign-studio/tests/Integration/Actions packages/campaign-studio/tests/Integration/Listeners
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/campaign-studio
git commit -m "feat: record campaign conversions"
```

## Task 4: Admin Resources

**Files:**

- Create: `packages/campaign-studio/resources/lang/en/form.php`
- Create: `packages/campaign-studio/resources/lang/en/navigation.php`
- Create: `packages/campaign-studio/src/Enums/ResourceEnum.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignGroups/CampaignGroupResource.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignGroups/Pages/ListCampaignGroups.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignGroups/Pages/CreateCampaignGroup.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignGroups/Pages/EditCampaignGroup.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignGroups/Schemas/CampaignGroupForm.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignGroups/Tables/CampaignGroupsTable.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/CampaignLandingPageResource.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/Pages/ListCampaignLandingPages.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/Pages/CreateCampaignLandingPage.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/Pages/EditCampaignLandingPage.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/Schemas/CampaignLandingPageForm.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignLandingPages/Tables/CampaignLandingPagesTable.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/CampaignCtaBlockResource.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/Pages/ListCampaignCtaBlocks.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/Pages/CreateCampaignCtaBlock.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/Pages/EditCampaignCtaBlock.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/Schemas/CampaignCtaBlockForm.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignCtaBlocks/Tables/CampaignCtaBlocksTable.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/CampaignConversionGoalResource.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/Pages/ListCampaignConversionGoals.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/Pages/CreateCampaignConversionGoal.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/Pages/EditCampaignConversionGoal.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/Schemas/CampaignConversionGoalForm.php`
- Create: `packages/campaign-studio/src/Filament/Resources/CampaignConversionGoals/Tables/CampaignConversionGoalsTable.php`
- Create: `packages/campaign-studio/tests/Feature/Filament/CampaignResourcesTest.php`

- [ ] **Step 1: Write failing resource tests**

Test that:

- Each resource class exists.
- Each resource only registers navigation when `capell-app/campaign-studio` is installed.
- Group, landing page, CTA block, and conversion goal form-builder expose the expected schema fields.
- Tables include status and date columns.

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Feature/Filament/CampaignResourcesTest.php
```

Expected: FAIL because resources do not exist.

- [ ] **Step 2: Add resource enum and register resources**

Create `ResourceEnum` with cases for group, landing page, CTA block, and conversion goal.

In `AdminServiceProvider`, register each resource through `CapellAdmin::registerResource(...)`.

- [ ] **Step 3: Add resource form-builder**

FormBuilder should use tabs:

- Details: name, slug/key, site, status, dates.
- Attribution: default UTM fields.
- Goals: primary goal selection for landing pages.
- Content: CTA headline/body/actions for CTA blocks.

Use translated labels and Filament method overrides. Do not use static string label properties.

- [ ] **Step 4: Add resource tables**

Tables should include:

- Name.
- Campaign group.
- Status.
- Active window.
- Primary conversion goal.
- Conversion count where relevant.

- [ ] **Step 5: Verify**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Feature/Filament/CampaignResourcesTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/campaign-studio
git commit -m "feat: add campaign admin resources"
```

## Task 5: LayoutBuilder Campaign Widgets And CTA Blocks

**Files:**

- Create: `packages/campaign-studio/src/Enums/CampaignWidgetComponentEnum.php`
- Create: `packages/campaign-studio/src/Enums/CampaignWidgetConfiguratorEnum.php`
- Create: `packages/campaign-studio/src/Filament/Configurators/Widgets/CampaignHeroWidgetConfigurator.php`
- Create: `packages/campaign-studio/src/Filament/Configurators/Widgets/CampaignCtaBlockWidgetConfigurator.php`
- Create: `packages/campaign-studio/src/Filament/Configurators/Widgets/CampaignLeadFormWidgetConfigurator.php`
- Create: `packages/campaign-studio/resources/views/components/widget/campaign-hero.blade.php`
- Create: `packages/campaign-studio/resources/views/components/widget/campaign-cta-block.blade.php`
- Create: `packages/campaign-studio/resources/views/components/widget/campaign-lead-form.blade.php`
- Create: `packages/campaign-studio/resources/views/components/tracking/attributes.blade.php`
- Create: `packages/campaign-studio/tests/Feature/LayoutBuilder/CampaignWidgetsTest.php`

- [ ] **Step 1: Write failing widget registration tests**

Test that:

- The CampaignStudio provider registers component enum cases with Capell Core.
- Widget configurators are registered with Capell Admin.
- CTA widget views render `data-campaign-id`, `data-campaign-goal`, and UTM-aware URLs.

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Feature/LayoutBuilder/CampaignWidgetsTest.php
```

Expected: FAIL because widget components do not exist.

- [ ] **Step 2: Add campaign widget types**

Register these components:

- `campaign-hero`: hero with eyebrow, headline, body, primary CTA, secondary CTA, proof strip.
- `campaign-cta-block`: reusable CTA block selected from `CampaignCtaBlock`.
- `campaign-lead-form`: Form package form selector plus campaign goal selector.

Use LayoutBuilder wrapper views and keep styling in utility classes that themes can override.

- [ ] **Step 3: Add CTA tracking attributes**

The tracking partial should render:

```blade
data-campaign-id="{{ $campaignGroup?->getKey() }}"
data-campaign-goal="{{ $conversionGoal?->key }}"
data-campaign-location="{{ $location }}"
```

Also append missing UTM parameters to CTA hrefs using a package action instead of inline string manipulation in Blade.

- [ ] **Step 4: Add URL building action**

Create `BuildCampaignUrlAction::handle(string $url, UtmData $utm): string`. It should:

- Preserve existing query parameters.
- Add only missing UTM keys.
- Return the original URL unchanged when no UTM fields are present.

- [ ] **Step 5: Verify**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Feature/LayoutBuilder/CampaignWidgetsTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/campaign-studio
git commit -m "feat: add campaign layout-builder widgets"
```

## Task 6: Campaign Layout Presets

**Files:**

- Create: `packages/campaign-studio/src/Actions/InstallCampaignLayoutsAction.php`
- Create: `packages/campaign-studio/src/Console/Commands/InstallCampaignLayoutsCommand.php`
- Create: `packages/campaign-studio/src/Support/LayoutPresets/CampaignLayoutPreset.php`
- Create: `packages/campaign-studio/src/Support/LayoutPresets/LeadGenerationPreset.php`
- Create: `packages/campaign-studio/src/Support/LayoutPresets/ProductLaunchPreset.php`
- Create: `packages/campaign-studio/src/Support/LayoutPresets/WebinarPreset.php`
- Create: `packages/campaign-studio/tests/Integration/Actions/InstallCampaignLayoutsActionTest.php`

- [ ] **Step 1: Write failing layout install tests**

Test that:

- Running `InstallCampaignLayoutsAction::run()` creates three layouts.
- Re-running the action is idempotent.
- Layouts contain LayoutBuilder containers and campaign widget keys.

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Integration/Actions/InstallCampaignLayoutsActionTest.php
```

Expected: FAIL because layout presets do not exist.

- [ ] **Step 2: Implement layout presets**

Create presets:

- Lead Generation: hero, proof strip, benefits, form CTA, FAQ.
- Product Launch: hero, feature grid, comparison, CTA strip, conversion form.
- Webinar/Event: hero, schedule, speaker proof, registration form, urgency CTA.

Store preset definitions as PHP classes returning arrays compatible with LayoutBuilder `layouts.containers` and `layouts.widgets`.

- [ ] **Step 3: Implement install action and command**

The action should:

- Create or update layouts by stable key.
- Avoid overwriting manually edited layouts unless a `force` argument is true.
- Use existing LayoutBuilder models and creator conventions.

The command should call the action and report created, updated, skipped counts.

- [ ] **Step 4: Verify**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Integration/Actions/InstallCampaignLayoutsActionTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/campaign-studio
git commit -m "feat: add campaign layout presets"
```

## Task 7: Insights Reporting Integration

**Files:**

- Create: `packages/campaign-studio/src/Data/Dashboard/CampaignConversionSummaryData.php`
- Create: `packages/campaign-studio/src/Data/Dashboard/CampaignLandingPageSummaryData.php`
- Create: `packages/campaign-studio/src/Actions/BuildCampaignOverviewStatsAction.php`
- Create: `packages/campaign-studio/src/Actions/BuildCampaignConversionFunnelAction.php`
- Create: `packages/campaign-studio/src/Actions/BuildTopCampaignStudioQueryAction.php`
- Create: `packages/campaign-studio/src/Actions/BuildTopLandingPagesQueryAction.php`
- Create: `packages/campaign-studio/src/Filament/Widgets/CampaignOverviewStatsWidget.php`
- Create: `packages/campaign-studio/src/Filament/Widgets/TopCampaignStudioWidget.php`
- Create: `packages/campaign-studio/src/Filament/Widgets/TopLandingPagesWidget.php`
- Create: `packages/campaign-studio/tests/Feature/Filament/CampaignInsightsWidgetsTest.php`
- Create: `packages/campaign-studio/tests/Unit/Actions/CampaignReportingActionsTest.php`

- [ ] **Step 1: Write failing reporting tests**

Test:

- Conversion totals are grouped by campaign.
- Conversion rate uses Insights visits for the matching `utm_campaign`.
- Top landing pages sort by conversions descending, then page name.
- Widgets return records without requiring HTTP requests.

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Unit/Actions/CampaignReportingActionsTest.php packages/campaign-studio/tests/Feature/Filament/CampaignInsightsWidgetsTest.php
```

Expected: FAIL because reporting actions and widgets do not exist.

- [ ] **Step 2: Implement reporting actions**

Use Insights models directly:

- Visits from `InsightsVisit` filtered by `utm_campaign`.
- Events from `InsightsEvent` linked through conversion records.
- Conversions from `CampaignConversion`.

Return Data objects, not raw arrays, from public action boundaries.

- [ ] **Step 3: Add Filament dashboard widgets**

Register widgets in `AdminServiceProvider`.

Widgets:

- Campaign overview stats: active campaign-studio, conversions, average conversion rate.
- Top campaign-studio table.
- Top landing pages table.

Use `GatedByRoleAndSettings` if the Insights widgets use the same convention.

- [ ] **Step 4: Verify**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Unit/Actions/CampaignReportingActionsTest.php packages/campaign-studio/tests/Feature/Filament/CampaignInsightsWidgetsTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/campaign-studio
git commit -m "feat: add campaign insights reporting"
```

## Task 8: SEO Suite And Page Schema Extension

**Files:**

- Create: `packages/campaign-studio/src/Filament/Extenders/Page/CampaignPageSchemaExtender.php`
- Create: `packages/campaign-studio/src/Actions/ApplyCampaignPageDefaultsAction.php`
- Create: `packages/campaign-studio/tests/Feature/PageSchema/CampaignPageSchemaExtenderTest.php`

- [ ] **Step 1: Write failing schema extender tests**

Test:

- The extender is tagged with `PageSchemaExtender::TAG`.
- Campaign fields appear on page form-builder.
- Selecting a campaign group can populate UTM defaults.
- SEO Suite absence does not break boot.

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Feature/PageSchema/CampaignPageSchemaExtenderTest.php
```

Expected: FAIL because the schema extender does not exist.

- [ ] **Step 2: Add page schema extender**

Fields:

- Campaign group selector.
- Landing page toggle.
- Primary conversion goal selector.
- UTM content and term fields.

Do not write SEO metadata directly unless SEO Suite is installed and its extension point is available. Use `class_exists()` checks and keep the integration optional.

- [ ] **Step 3: Register the extender**

In `CampaignStudioServiceProvider`, bind the extender as singleton and tag it with `PageSchemaExtender::TAG`.

- [ ] **Step 4: Verify**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Feature/PageSchema/CampaignPageSchemaExtenderTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/campaign-studio
git commit -m "feat: add campaign page schema"
```

## Task 9: Cross-Package Boot, Architecture Tests, And Docs

**Files:**

- Create: `packages/campaign-studio/README.md`
- Create: `packages/campaign-studio/docs/campaign-studio-api.md`
- Create: `packages/campaign-studio/docs/campaign-studio-database.md`
- Create: `packages/campaign-studio/tests/Arch/CampaignStudioPackageTest.php`
- Create: `packages/campaign-studio/tests/Unit/ManifestRequirementsTest.php`
- Modify: `tests/Packages/PackagesTestCase.php`
- Modify: `tests/Packages/Integration/CrossPackageBootTest.php`

- [ ] **Step 1: Write architecture and manifest tests**

Test:

- PHP files declare strict types.
- CampaignStudio does not import package internals outside allowed dependencies.
- `capell.json` declares LayoutBuilder, FormBuilder, and Insights dependencies.
- `composer.json` provider discovery points to `CampaignStudioServiceProvider`.

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests/Arch packages/campaign-studio/tests/Unit/ManifestRequirementsTest.php
```

Expected: PASS once package files are complete.

- [ ] **Step 2: Add docs**

Document:

- How CampaignGroup, CampaignLandingPage, CampaignCtaBlock, ConversionGoal, and Conversion relate.
- How UTM attribution works with Insights visits.
- How FormBuilder submissions become conversions.
- How to install campaign layouts.
- How to add a new campaign widget.

- [ ] **Step 3: Add cross-package boot coverage**

Update the shared package tests to register and force-install CampaignStudio after LayoutBuilder, FormBuilder, Insights, and SEO Suite.

- [ ] **Step 4: Verify package test suite**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/campaign-studio tests/Packages
git commit -m "test: cover campaign-studio package integration"
```

## Task 10: Full Verification

**Files:**

- No new files.

- [ ] **Step 1: Run package tests**

```bash
vendor/bin/pest packages/campaign-studio/tests
```

Expected: PASS.

- [ ] **Step 2: Run affected package tests**

```bash
vendor/bin/pest packages/insights/tests packages/form-builder/tests packages/layout-builder/tests packages/campaign-studio/tests
```

Expected: PASS.

- [ ] **Step 3: Run full test suite when the branch is ready**

```bash
composer test
```

Expected: PASS.

- [ ] **Step 4: Run preflight**

```bash
composer preflight
```

Expected: PASS.

- [ ] **Step 5: Commit any final fixes**

```bash
git add packages/campaign-studio composer.json tests/Packages
git commit -m "chore: finalize campaign-studio package"
```

## Implementation Notes

- Prefer using existing Insights UTM fields on `insights_visits`; do not add duplicate visit attribution tables in CampaignStudio.
- Store conversion-specific snapshots in `campaign_conversions.attribution` so historical reporting survives later campaign edits.
- Do not make campaign landing pages a new Core page type in v1. Link CampaignStudio records to existing Core pages so LayoutBuilder, SEO Suite, PublishingStudio, and Navigation keep their current responsibilities.
- Make campaign layout presets installable data, not hard-coded theme behavior.
- CTA blocks are reusable campaign content, while LayoutBuilder widgets decide placement and rendering.
- Every public reporting action should return Data objects or typed collections so dashboard widgets stay thin.
