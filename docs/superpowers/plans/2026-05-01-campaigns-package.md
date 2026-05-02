# Campaigns Package Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `capell-app/campaigns` as a marketing package for campaign groups, landing pages, UTM attribution, reusable CTA blocks, conversion goals, campaign-focused Mosaic layouts, and Analytics reporting.

**Architecture:** Campaigns composes existing Capell packages instead of duplicating them. Mosaic remains the layout and widget engine, Forms remains the lead capture engine, Analytics remains the visit/event store, and SEO Tools remains responsible for page metadata. The campaigns package owns campaign domain models, admin resources, conversion attribution actions, Mosaic campaign widgets, and reporting widgets.

**Tech Stack:** PHP 8.2, Laravel package tools, Pest, Lorisleiva Actions, Spatie Laravel Data, Filament resources/widgets, Capell Core/Admin/Frontend, Mosaic, Forms, Analytics, and optional SEO Tools integration through page schema extenders.

---

## Ground Rules

- Work in `/Users/ben/Sites/packages/capell/capell-packages-4`.
- Preserve the current dirty worktree. Only edit or stage files created or changed for the campaigns package.
- Do not run `php artisan`; use `vendor/bin/pest` directly.
- Every PHP file must start with `declare(strict_types=1);`.
- All closures must declare parameter and return types.
- Do not introduce single-letter or cryptic variable names.
- Domain behavior lives in `packages/campaigns/src/Actions`.
- Structured input/output lives in `packages/campaigns/src/Data`.
- Persisted string values use backed enums in `packages/campaigns/src/Enums`.
- User-facing strings use `__('capell-campaigns::...')`.
- The package may import Mosaic, Forms, Analytics, and SEO Tools classes. Core must not import Campaigns classes.

## Scope

Build the first useful version of Campaigns:

- Campaign groups with status, date window, budget metadata, and default UTM values.
- Landing page records that link a campaign group to an existing Capell page.
- Conversion goals that can target page views, CTA clicks, form submissions, or custom Analytics actions.
- Conversion records that store attributed Analytics visit/event data.
- Reusable CTA blocks with buttons, destination URLs, and default UTM tagging.
- Campaign Mosaic widgets and installable campaign layout presets.
- Admin resources for groups, landing pages, CTA blocks, and conversion goals.
- Analytics dashboard widgets for conversions, conversion rate, top campaigns, and top landing pages.

Leave these out of v1:

- External ad platform imports.
- A/B testing and variant traffic splitting.
- External CRM sync.
- GA4 forwarding.
- Attribution models beyond first-touch and last-touch fields stored on conversion records.

## File Structure

Create these files:

- `packages/campaigns/composer.json`: package metadata and provider discovery.
- `packages/campaigns/capell.json`: Capell package manifest with dependencies.
- `packages/campaigns/config/capell-campaigns.php`: table names, UTM keys, conversion cookie, and layout preset settings.
- `packages/campaigns/resources/lang/en/package.php`: package description.
- `packages/campaigns/resources/lang/en/generic.php`: shared labels.
- `packages/campaigns/resources/lang/en/form.php`: form labels.
- `packages/campaigns/resources/lang/en/navigation.php`: navigation labels.
- `packages/campaigns/resources/lang/en/widgets.php`: dashboard widget labels.
- `packages/campaigns/resources/views/components/widget/campaign-hero.blade.php`: campaign hero Mosaic widget view.
- `packages/campaigns/resources/views/components/widget/campaign-cta-block.blade.php`: reusable CTA block Mosaic widget view.
- `packages/campaigns/resources/views/components/widget/campaign-lead-form.blade.php`: lead form Mosaic widget view.
- `packages/campaigns/resources/views/components/tracking/attributes.blade.php`: reusable tracking attributes partial.
- `packages/campaigns/src/Providers/CampaignsServiceProvider.php`: shared package registration.
- `packages/campaigns/src/Providers/AdminServiceProvider.php`: Filament resources and widgets.
- `packages/campaigns/src/Providers/FrontendServiceProvider.php`: frontend components and render hooks.
- `packages/campaigns/src/Enums/CampaignStatus.php`: campaign lifecycle enum.
- `packages/campaigns/src/Enums/ConversionGoalType.php`: conversion goal trigger enum.
- `packages/campaigns/src/Enums/AttributionModel.php`: attribution model enum.
- `packages/campaigns/src/Enums/ResourceEnum.php`: admin resource enum.
- `packages/campaigns/src/Enums/CampaignWidgetComponentEnum.php`: Mosaic widget view enum.
- `packages/campaigns/src/Enums/CampaignWidgetConfiguratorEnum.php`: Mosaic widget configurator enum.
- `packages/campaigns/src/Data/UtmData.php`: structured UTM values.
- `packages/campaigns/src/Data/CampaignCtaActionData.php`: CTA action value object.
- `packages/campaigns/src/Data/ConversionAttributionData.php`: conversion attribution snapshot.
- `packages/campaigns/src/Data/Dashboard/CampaignConversionSummaryData.php`: campaign dashboard summary.
- `packages/campaigns/src/Data/Dashboard/CampaignLandingPageSummaryData.php`: landing page dashboard summary.
- `packages/campaigns/src/Models/CampaignGroup.php`: campaign group model.
- `packages/campaigns/src/Models/CampaignLandingPage.php`: campaign landing page model.
- `packages/campaigns/src/Models/CampaignCtaBlock.php`: reusable CTA block model.
- `packages/campaigns/src/Models/CampaignConversionGoal.php`: conversion goal model.
- `packages/campaigns/src/Models/CampaignConversion.php`: recorded conversion model.
- `packages/campaigns/src/Actions/ResolveCampaignFromUrlAction.php`: resolve campaign from UTM or page URL.
- `packages/campaigns/src/Actions/BuildCampaignUrlAction.php`: append missing UTM values to CTA URLs.
- `packages/campaigns/src/Actions/BuildConversionAttributionAction.php`: build conversion attribution snapshots.
- `packages/campaigns/src/Actions/RecordCampaignConversionAction.php`: record idempotent conversions.
- `packages/campaigns/src/Actions/RecordCtaClickConversionAction.php`: record CTA click conversions.
- `packages/campaigns/src/Actions/RecordFormSubmissionConversionAction.php`: record form submission conversions.
- `packages/campaigns/src/Actions/RecordPageViewConversionAction.php`: record page view conversions.
- `packages/campaigns/src/Actions/InstallCampaignLayoutsAction.php`: install Mosaic campaign layouts.
- `packages/campaigns/src/Actions/BuildCampaignOverviewStatsAction.php`: dashboard totals.
- `packages/campaigns/src/Actions/BuildCampaignConversionFunnelAction.php`: funnel summaries.
- `packages/campaigns/src/Actions/BuildTopCampaignsQueryAction.php`: top campaigns report.
- `packages/campaigns/src/Actions/BuildTopLandingPagesQueryAction.php`: top landing pages report.
- `packages/campaigns/src/Listeners/RecordFormSubmissionConversion.php`: Forms event listener.
- `packages/campaigns/src/Filament/Resources/CampaignGroups/CampaignGroupResource.php`: campaign group resource.
- `packages/campaigns/src/Filament/Resources/CampaignLandingPages/CampaignLandingPageResource.php`: landing page resource.
- `packages/campaigns/src/Filament/Resources/CampaignCtaBlocks/CampaignCtaBlockResource.php`: CTA block resource.
- `packages/campaigns/src/Filament/Resources/CampaignConversionGoals/CampaignConversionGoalResource.php`: conversion goal resource.
- `packages/campaigns/src/Filament/Configurators/Widgets/CampaignHeroWidgetConfigurator.php`: campaign hero widget form.
- `packages/campaigns/src/Filament/Configurators/Widgets/CampaignCtaBlockWidgetConfigurator.php`: CTA block widget form.
- `packages/campaigns/src/Filament/Configurators/Widgets/CampaignLeadFormWidgetConfigurator.php`: lead form widget form.
- `packages/campaigns/src/Filament/Extenders/Page/CampaignPageSchemaExtender.php`: page campaign metadata fields.
- `packages/campaigns/src/Filament/Widgets/CampaignOverviewStatsWidget.php`: campaign stats dashboard widget.
- `packages/campaigns/src/Filament/Widgets/TopCampaignsWidget.php`: top campaigns dashboard widget.
- `packages/campaigns/src/Filament/Widgets/TopLandingPagesWidget.php`: top landing pages dashboard widget.
- `packages/campaigns/database/migrations/create_campaign_groups_table.php`: campaign groups table.
- `packages/campaigns/database/migrations/create_campaign_landing_pages_table.php`: landing pages table.
- `packages/campaigns/database/migrations/create_campaign_cta_blocks_table.php`: CTA blocks table.
- `packages/campaigns/database/migrations/create_campaign_conversion_goals_table.php`: conversion goals table.
- `packages/campaigns/database/migrations/create_campaign_conversions_table.php`: conversions table.
- `packages/campaigns/database/factories/CampaignGroupFactory.php`: group factory.
- `packages/campaigns/database/factories/CampaignLandingPageFactory.php`: landing page factory.
- `packages/campaigns/database/factories/CampaignCtaBlockFactory.php`: CTA block factory.
- `packages/campaigns/database/factories/CampaignConversionGoalFactory.php`: goal factory.
- `packages/campaigns/database/factories/CampaignConversionFactory.php`: conversion factory.
- `packages/campaigns/tests/CampaignsTestCase.php`: package test case.
- `packages/campaigns/tests/Pest.php`: Pest setup.
- `packages/campaigns/tests/Unit/Providers/CampaignsServiceProviderTest.php`: provider smoke tests.
- `packages/campaigns/tests/Unit/Actions/ResolveCampaignFromUrlActionTest.php`: campaign URL resolution tests.
- `packages/campaigns/tests/Unit/Actions/CampaignReportingActionsTest.php`: reporting action tests.
- `packages/campaigns/tests/Integration/Database/CampaignMigrationsTest.php`: migration tests.
- `packages/campaigns/tests/Integration/Models/CampaignRelationshipsTest.php`: model relationship tests.
- `packages/campaigns/tests/Integration/Actions/RecordCampaignConversionActionTest.php`: conversion action tests.
- `packages/campaigns/tests/Integration/Actions/InstallCampaignLayoutsActionTest.php`: layout installer tests.
- `packages/campaigns/tests/Integration/Listeners/FormSubmissionConversionTest.php`: Forms listener tests.
- `packages/campaigns/tests/Feature/Filament/CampaignResourcesTest.php`: admin resource tests.
- `packages/campaigns/tests/Feature/Filament/CampaignAnalyticsWidgetsTest.php`: dashboard widget tests.
- `packages/campaigns/tests/Feature/Mosaic/CampaignWidgetsTest.php`: Mosaic widget tests.
- `packages/campaigns/tests/Feature/PageSchema/CampaignPageSchemaExtenderTest.php`: page schema extender tests.

Modify these files:

- `composer.json`: add `Capell\\Campaigns\\` and `Capell\\Campaigns\\Database\\Factories\\` autoload entries.
- `tests/Packages/PackagesTestCase.php`: include Campaigns in cross-package boot tests once package registration is implemented.
- `tests/Packages/Integration/CrossPackageBootTest.php`: assert Campaigns provider is discoverable.

## Task 1: Package Skeleton And Registration

**Files:**

- Create: `packages/campaigns/composer.json`
- Create: `packages/campaigns/capell.json`
- Create: `packages/campaigns/config/capell-campaigns.php`
- Create: `packages/campaigns/resources/lang/en/package.php`
- Create: `packages/campaigns/resources/lang/en/generic.php`
- Create: `packages/campaigns/src/Providers/CampaignsServiceProvider.php`
- Create: `packages/campaigns/src/Providers/AdminServiceProvider.php`
- Create: `packages/campaigns/src/Providers/FrontendServiceProvider.php`
- Create: `packages/campaigns/tests/Pest.php`
- Create: `packages/campaigns/tests/CampaignsTestCase.php`
- Create: `packages/campaigns/tests/Unit/Providers/CampaignsServiceProviderTest.php`
- Modify: `composer.json`

- [ ] **Step 1: Write the failing provider smoke tests**

Create `packages/campaigns/tests/Pest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Campaigns\Tests\CampaignsTestCase;

uses(CampaignsTestCase::class)->in(__DIR__);
```

Create `packages/campaigns/tests/Unit/Providers/CampaignsServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

use Capell\Campaigns\Providers\CampaignsServiceProvider;
use Capell\Core\Facades\CapellCore;

it('registers the campaigns package metadata', function (): void {
    $package = CapellCore::getPackage(CampaignsServiceProvider::$packageName);

    expect($package->name)->toBe(CampaignsServiceProvider::$packageName);
});

it('loads the campaigns config', function (): void {
    expect(config('capell-campaigns.tables.groups'))->toBe('campaign_groups')
        ->and(config('capell-campaigns.conversion_cookie'))->toBe('capell_campaign_visit');
});
```

- [ ] **Step 2: Run the smoke test and verify it fails**

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Unit/Providers/CampaignsServiceProviderTest.php
```

Expected: FAIL because the campaigns provider does not exist yet.

- [ ] **Step 3: Add package metadata and provider classes**

Create `packages/campaigns/capell.json`:

```json
{
    "name": "capell-app/campaigns",
    "description": "Campaign landing pages, CTA blocks, UTM attribution, conversion goals, and campaign analytics.",
    "providers": {
        "shared": ["Capell\\Campaigns\\Providers\\CampaignsServiceProvider"],
        "admin": ["Capell\\Campaigns\\Providers\\AdminServiceProvider"],
        "frontend": ["Capell\\Campaigns\\Providers\\FrontendServiceProvider"]
    },
    "dependencies": [
        "capell-app/mosaic",
        "capell-app/forms",
        "capell-app/analytics"
    ],
    "optional": ["capell-app/seo-tools"]
}
```

Create `packages/campaigns/config/capell-campaigns.php`:

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

Create `packages/campaigns/src/Providers/CampaignsServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Capell\Campaigns\Providers;

use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

final class CampaignsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-campaigns';

    public static string $packageName = 'capell-app/campaigns';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-campaigns')
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
            description: fn (): string => __('capell-campaigns::package.description'),
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
        foreach (config('capell-campaigns.tables', []) as $tableName) {
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

Create `packages/campaigns/tests/CampaignsTestCase.php` using the same provider style as Analytics, with Admin, Frontend, Mosaic, Forms, Analytics, and Campaigns providers installed through `CapellCore::forcePackageInstalled(...)`.

Modify root `composer.json` autoload sections:

```json
"Capell\\Campaigns\\": "packages/campaigns/src",
"Capell\\Campaigns\\Database\\Factories\\": "packages/campaigns/database/factories"
```

```json
"Capell\\Campaigns\\Tests\\": "packages/campaigns/tests"
```

- [ ] **Step 5: Verify package discovery**

Run:

```bash
composer dump-autoload
vendor/bin/pest packages/campaigns/tests/Unit/Providers/CampaignsServiceProviderTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add composer.json packages/campaigns
git commit -m "feat: add campaigns package skeleton"
```

## Task 2: Campaign Domain Tables, Models, Data, And Enums

**Files:**

- Create: `packages/campaigns/database/migrations/create_campaign_groups_table.php`
- Create: `packages/campaigns/database/migrations/create_campaign_landing_pages_table.php`
- Create: `packages/campaigns/database/migrations/create_campaign_cta_blocks_table.php`
- Create: `packages/campaigns/database/migrations/create_campaign_conversion_goals_table.php`
- Create: `packages/campaigns/database/migrations/create_campaign_conversions_table.php`
- Create: `packages/campaigns/src/Enums/CampaignStatus.php`
- Create: `packages/campaigns/src/Enums/ConversionGoalType.php`
- Create: `packages/campaigns/src/Enums/AttributionModel.php`
- Create: `packages/campaigns/src/Data/UtmData.php`
- Create: `packages/campaigns/src/Data/CampaignCtaActionData.php`
- Create: `packages/campaigns/src/Data/ConversionAttributionData.php`
- Create: `packages/campaigns/src/Models/CampaignGroup.php`
- Create: `packages/campaigns/src/Models/CampaignLandingPage.php`
- Create: `packages/campaigns/src/Models/CampaignCtaBlock.php`
- Create: `packages/campaigns/src/Models/CampaignConversionGoal.php`
- Create: `packages/campaigns/src/Models/CampaignConversion.php`
- Create: `packages/campaigns/database/factories/CampaignGroupFactory.php`
- Create: `packages/campaigns/database/factories/CampaignLandingPageFactory.php`
- Create: `packages/campaigns/database/factories/CampaignCtaBlockFactory.php`
- Create: `packages/campaigns/database/factories/CampaignConversionGoalFactory.php`
- Create: `packages/campaigns/database/factories/CampaignConversionFactory.php`
- Create: `packages/campaigns/tests/Integration/Database/CampaignMigrationsTest.php`
- Create: `packages/campaigns/tests/Integration/Models/CampaignRelationshipsTest.php`

- [ ] **Step 1: Write failing migration and model relationship tests**

Test the following:

- The five campaign tables exist.
- A campaign group has many landing pages, CTA blocks, conversion goals, and conversions.
- A landing page belongs to a campaign group and a Core page.
- A conversion belongs to a goal and optionally an Analytics visit/event.
- JSON casts return `UtmData`, `CampaignCtaActionData` collection, and `ConversionAttributionData`.

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Integration/Database packages/campaigns/tests/Integration/Models
```

Expected: FAIL because migrations and models do not exist.

- [ ] **Step 2: Add migrations**

Use table names from `config('capell-campaigns.tables.*')`. Include:

- `campaign_groups`: `site_id`, `name`, `slug`, `status`, `starts_at`, `ends_at`, `utm_source`, `utm_medium`, `utm_campaign`, `budget_amount`, `notes`, timestamps, soft deletes.
- `campaign_landing_pages`: `campaign_group_id`, `page_id`, `headline`, `primary_goal_id`, `utm_content`, `utm_term`, `is_primary`, timestamps.
- `campaign_cta_blocks`: `campaign_group_id`, `site_id`, `name`, `key`, `headline`, `body`, `actions`, `default_utm`, `is_active`, timestamps, soft deletes.
- `campaign_conversion_goals`: `campaign_group_id`, `site_id`, `name`, `key`, `type`, `target`, `value_amount`, `is_primary`, `is_active`, timestamps, soft deletes.
- `campaign_conversions`: `campaign_group_id`, `campaign_landing_page_id`, `campaign_conversion_goal_id`, `analytics_visit_id`, `analytics_event_id`, `site_id`, `language_id`, `attribution`, `converted_at`, timestamps.

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

namespace Capell\Campaigns\Data;

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

Models must use guarded or fillable consistently with nearby packages. Prefer explicit `$fillable` for Campaigns.

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

Add `CapellCore::registerModels([...])` to `CampaignsServiceProvider`.

- [ ] **Step 6: Verify**

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Integration/Database packages/campaigns/tests/Integration/Models
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/campaigns
git commit -m "feat: add campaign domain models"
```

## Task 3: Campaign Attribution And Conversion Actions

**Files:**

- Create: `packages/campaigns/src/Actions/ResolveCampaignFromUrlAction.php`
- Create: `packages/campaigns/src/Actions/BuildConversionAttributionAction.php`
- Create: `packages/campaigns/src/Actions/RecordCampaignConversionAction.php`
- Create: `packages/campaigns/src/Actions/RecordCtaClickConversionAction.php`
- Create: `packages/campaigns/src/Actions/RecordFormSubmissionConversionAction.php`
- Create: `packages/campaigns/src/Actions/RecordPageViewConversionAction.php`
- Create: `packages/campaigns/src/Listeners/RecordFormSubmissionConversion.php`
- Create: `packages/campaigns/tests/Unit/Actions/ResolveCampaignFromUrlActionTest.php`
- Create: `packages/campaigns/tests/Integration/Actions/RecordCampaignConversionActionTest.php`
- Create: `packages/campaigns/tests/Integration/Listeners/FormSubmissionConversionTest.php`

- [ ] **Step 1: Write failing action tests**

Test:

- `ResolveCampaignFromUrlAction` matches `utm_campaign` to active `CampaignGroup::slug`.
- `ResolveCampaignFromUrlAction` falls back to a configured landing page by page path.
- `RecordCampaignConversionAction` is idempotent for the same goal, visit, and event.
- Form submissions with a matching goal create a conversion.
- Disabled goals do not create conversions.

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Unit/Actions packages/campaigns/tests/Integration/Actions packages/campaigns/tests/Integration/Listeners
```

Expected: FAIL because actions do not exist.

- [ ] **Step 2: Implement URL resolution**

`ResolveCampaignFromUrlAction::handle(string $url): ?CampaignGroup` should:

- Parse query values using `parse_url()` and `parse_str()`.
- Match `utm_campaign` to `CampaignGroup.slug` first.
- Match URL path to a `CampaignLandingPage` page translation URL second, using the existing page model relationships available in Core.
- Return `null` when no active campaign matches.

- [ ] **Step 3: Implement conversion attribution**

`BuildConversionAttributionAction::handle(?AnalyticsVisit $visit, ?AnalyticsEvent $event): ConversionAttributionData` should store:

- landing URL
- referrer URL
- UTM source, medium, campaign, term, content
- event name
- event label
- event location
- first-touch campaign from visit fields
- last-touch campaign from event metadata when available

- [ ] **Step 4: Implement idempotent conversion recording**

`RecordCampaignConversionAction::handle(CampaignConversionGoal $goal, ?AnalyticsVisit $visit, ?AnalyticsEvent $event, ?CampaignLandingPage $landingPage = null): ?CampaignConversion` should:

- Return `null` when the goal is inactive.
- Resolve the campaign group from the goal.
- Use `firstOrCreate` on `campaign_conversion_goal_id`, `analytics_visit_id`, and `analytics_event_id`.
- Store `converted_at` from the event occurrence or `now()->toImmutable()`.
- Store attribution via `BuildConversionAttributionAction::run(...)`.

- [ ] **Step 5: Wire Forms integration**

Listen to `Capell\Forms\Events\FormSubmitted`. For goals with `type = FormSubmission`, match goal `target` against the submitted form handle or form id. Use the submission meta URL to resolve the landing page and visit when possible.

- [ ] **Step 6: Verify**

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Unit/Actions packages/campaigns/tests/Integration/Actions packages/campaigns/tests/Integration/Listeners
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/campaigns
git commit -m "feat: record campaign conversions"
```

## Task 4: Admin Resources

**Files:**

- Create: `packages/campaigns/resources/lang/en/form.php`
- Create: `packages/campaigns/resources/lang/en/navigation.php`
- Create: `packages/campaigns/src/Enums/ResourceEnum.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignGroups/CampaignGroupResource.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignGroups/Pages/ListCampaignGroups.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignGroups/Pages/CreateCampaignGroup.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignGroups/Pages/EditCampaignGroup.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignGroups/Schemas/CampaignGroupForm.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignGroups/Tables/CampaignGroupsTable.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignLandingPages/CampaignLandingPageResource.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignLandingPages/Pages/ListCampaignLandingPages.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignLandingPages/Pages/CreateCampaignLandingPage.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignLandingPages/Pages/EditCampaignLandingPage.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignLandingPages/Schemas/CampaignLandingPageForm.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignLandingPages/Tables/CampaignLandingPagesTable.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignCtaBlocks/CampaignCtaBlockResource.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignCtaBlocks/Pages/ListCampaignCtaBlocks.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignCtaBlocks/Pages/CreateCampaignCtaBlock.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignCtaBlocks/Pages/EditCampaignCtaBlock.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignCtaBlocks/Schemas/CampaignCtaBlockForm.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignCtaBlocks/Tables/CampaignCtaBlocksTable.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignConversionGoals/CampaignConversionGoalResource.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignConversionGoals/Pages/ListCampaignConversionGoals.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignConversionGoals/Pages/CreateCampaignConversionGoal.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignConversionGoals/Pages/EditCampaignConversionGoal.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignConversionGoals/Schemas/CampaignConversionGoalForm.php`
- Create: `packages/campaigns/src/Filament/Resources/CampaignConversionGoals/Tables/CampaignConversionGoalsTable.php`
- Create: `packages/campaigns/tests/Feature/Filament/CampaignResourcesTest.php`

- [ ] **Step 1: Write failing resource tests**

Test that:

- Each resource class exists.
- Each resource only registers navigation when `capell-app/campaigns` is installed.
- Group, landing page, CTA block, and conversion goal forms expose the expected schema fields.
- Tables include status and date columns.

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Feature/Filament/CampaignResourcesTest.php
```

Expected: FAIL because resources do not exist.

- [ ] **Step 2: Add resource enum and register resources**

Create `ResourceEnum` with cases for group, landing page, CTA block, and conversion goal.

In `AdminServiceProvider`, register each resource through `CapellAdmin::registerResource(...)`.

- [ ] **Step 3: Add resource forms**

Forms should use tabs:

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
vendor/bin/pest packages/campaigns/tests/Feature/Filament/CampaignResourcesTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/campaigns
git commit -m "feat: add campaign admin resources"
```

## Task 5: Mosaic Campaign Widgets And CTA Blocks

**Files:**

- Create: `packages/campaigns/src/Enums/CampaignWidgetComponentEnum.php`
- Create: `packages/campaigns/src/Enums/CampaignWidgetConfiguratorEnum.php`
- Create: `packages/campaigns/src/Filament/Configurators/Widgets/CampaignHeroWidgetConfigurator.php`
- Create: `packages/campaigns/src/Filament/Configurators/Widgets/CampaignCtaBlockWidgetConfigurator.php`
- Create: `packages/campaigns/src/Filament/Configurators/Widgets/CampaignLeadFormWidgetConfigurator.php`
- Create: `packages/campaigns/resources/views/components/widget/campaign-hero.blade.php`
- Create: `packages/campaigns/resources/views/components/widget/campaign-cta-block.blade.php`
- Create: `packages/campaigns/resources/views/components/widget/campaign-lead-form.blade.php`
- Create: `packages/campaigns/resources/views/components/tracking/attributes.blade.php`
- Create: `packages/campaigns/tests/Feature/Mosaic/CampaignWidgetsTest.php`

- [ ] **Step 1: Write failing widget registration tests**

Test that:

- The Campaigns provider registers component enum cases with Capell Core.
- Widget configurators are registered with Capell Admin.
- CTA widget views render `data-campaign-id`, `data-campaign-goal`, and UTM-aware URLs.

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Feature/Mosaic/CampaignWidgetsTest.php
```

Expected: FAIL because widget components do not exist.

- [ ] **Step 2: Add campaign widget types**

Register these components:

- `campaign-hero`: hero with eyebrow, headline, body, primary CTA, secondary CTA, proof strip.
- `campaign-cta-block`: reusable CTA block selected from `CampaignCtaBlock`.
- `campaign-lead-form`: Form package form selector plus campaign goal selector.

Use Mosaic wrapper views and keep styling in utility classes that themes can override.

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
vendor/bin/pest packages/campaigns/tests/Feature/Mosaic/CampaignWidgetsTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/campaigns
git commit -m "feat: add campaign mosaic widgets"
```

## Task 6: Campaign Layout Presets

**Files:**

- Create: `packages/campaigns/src/Actions/InstallCampaignLayoutsAction.php`
- Create: `packages/campaigns/src/Console/Commands/InstallCampaignLayoutsCommand.php`
- Create: `packages/campaigns/src/Support/LayoutPresets/CampaignLayoutPreset.php`
- Create: `packages/campaigns/src/Support/LayoutPresets/LeadGenerationPreset.php`
- Create: `packages/campaigns/src/Support/LayoutPresets/ProductLaunchPreset.php`
- Create: `packages/campaigns/src/Support/LayoutPresets/WebinarPreset.php`
- Create: `packages/campaigns/tests/Integration/Actions/InstallCampaignLayoutsActionTest.php`

- [ ] **Step 1: Write failing layout install tests**

Test that:

- Running `InstallCampaignLayoutsAction::run()` creates three layouts.
- Re-running the action is idempotent.
- Layouts contain Mosaic containers and campaign widget keys.

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Integration/Actions/InstallCampaignLayoutsActionTest.php
```

Expected: FAIL because layout presets do not exist.

- [ ] **Step 2: Implement layout presets**

Create presets:

- Lead Generation: hero, proof strip, benefits, form CTA, FAQ.
- Product Launch: hero, feature grid, comparison, CTA strip, conversion form.
- Webinar/Event: hero, schedule, speaker proof, registration form, urgency CTA.

Store preset definitions as PHP classes returning arrays compatible with Mosaic `layouts.containers` and `layouts.widgets`.

- [ ] **Step 3: Implement install action and command**

The action should:

- Create or update layouts by stable key.
- Avoid overwriting manually edited layouts unless a `force` argument is true.
- Use existing Mosaic models and creator conventions.

The command should call the action and report created, updated, skipped counts.

- [ ] **Step 4: Verify**

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Integration/Actions/InstallCampaignLayoutsActionTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/campaigns
git commit -m "feat: add campaign layout presets"
```

## Task 7: Analytics Reporting Integration

**Files:**

- Create: `packages/campaigns/src/Data/Dashboard/CampaignConversionSummaryData.php`
- Create: `packages/campaigns/src/Data/Dashboard/CampaignLandingPageSummaryData.php`
- Create: `packages/campaigns/src/Actions/BuildCampaignOverviewStatsAction.php`
- Create: `packages/campaigns/src/Actions/BuildCampaignConversionFunnelAction.php`
- Create: `packages/campaigns/src/Actions/BuildTopCampaignsQueryAction.php`
- Create: `packages/campaigns/src/Actions/BuildTopLandingPagesQueryAction.php`
- Create: `packages/campaigns/src/Filament/Widgets/CampaignOverviewStatsWidget.php`
- Create: `packages/campaigns/src/Filament/Widgets/TopCampaignsWidget.php`
- Create: `packages/campaigns/src/Filament/Widgets/TopLandingPagesWidget.php`
- Create: `packages/campaigns/tests/Feature/Filament/CampaignAnalyticsWidgetsTest.php`
- Create: `packages/campaigns/tests/Unit/Actions/CampaignReportingActionsTest.php`

- [ ] **Step 1: Write failing reporting tests**

Test:

- Conversion totals are grouped by campaign.
- Conversion rate uses Analytics visits for the matching `utm_campaign`.
- Top landing pages sort by conversions descending, then page name.
- Widgets return records without requiring HTTP requests.

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Unit/Actions/CampaignReportingActionsTest.php packages/campaigns/tests/Feature/Filament/CampaignAnalyticsWidgetsTest.php
```

Expected: FAIL because reporting actions and widgets do not exist.

- [ ] **Step 2: Implement reporting actions**

Use Analytics models directly:

- Visits from `AnalyticsVisit` filtered by `utm_campaign`.
- Events from `AnalyticsEvent` linked through conversion records.
- Conversions from `CampaignConversion`.

Return Data objects, not raw arrays, from public action boundaries.

- [ ] **Step 3: Add Filament dashboard widgets**

Register widgets in `AdminServiceProvider`.

Widgets:

- Campaign overview stats: active campaigns, conversions, average conversion rate.
- Top campaigns table.
- Top landing pages table.

Use `GatedByRoleAndSettings` if the Analytics widgets use the same convention.

- [ ] **Step 4: Verify**

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Unit/Actions/CampaignReportingActionsTest.php packages/campaigns/tests/Feature/Filament/CampaignAnalyticsWidgetsTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/campaigns
git commit -m "feat: add campaign analytics reporting"
```

## Task 8: SEO Tools And Page Schema Extension

**Files:**

- Create: `packages/campaigns/src/Filament/Extenders/Page/CampaignPageSchemaExtender.php`
- Create: `packages/campaigns/src/Actions/ApplyCampaignPageDefaultsAction.php`
- Create: `packages/campaigns/tests/Feature/PageSchema/CampaignPageSchemaExtenderTest.php`

- [ ] **Step 1: Write failing schema extender tests**

Test:

- The extender is tagged with `PageSchemaExtender::TAG`.
- Campaign fields appear on page forms.
- Selecting a campaign group can populate UTM defaults.
- SEO Tools absence does not break boot.

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Feature/PageSchema/CampaignPageSchemaExtenderTest.php
```

Expected: FAIL because the schema extender does not exist.

- [ ] **Step 2: Add page schema extender**

Fields:

- Campaign group selector.
- Landing page toggle.
- Primary conversion goal selector.
- UTM content and term fields.

Do not write SEO metadata directly unless SEO Tools is installed and its extension point is available. Use `class_exists()` checks and keep the integration optional.

- [ ] **Step 3: Register the extender**

In `CampaignsServiceProvider`, bind the extender as singleton and tag it with `PageSchemaExtender::TAG`.

- [ ] **Step 4: Verify**

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Feature/PageSchema/CampaignPageSchemaExtenderTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/campaigns
git commit -m "feat: add campaign page schema"
```

## Task 9: Cross-Package Boot, Architecture Tests, And Docs

**Files:**

- Create: `packages/campaigns/README.md`
- Create: `packages/campaigns/docs/campaigns-api.md`
- Create: `packages/campaigns/docs/campaigns-database.md`
- Create: `packages/campaigns/tests/Arch/CampaignsPackageTest.php`
- Create: `packages/campaigns/tests/Unit/ManifestRequirementsTest.php`
- Modify: `tests/Packages/PackagesTestCase.php`
- Modify: `tests/Packages/Integration/CrossPackageBootTest.php`

- [ ] **Step 1: Write architecture and manifest tests**

Test:

- PHP files declare strict types.
- Campaigns does not import package internals outside allowed dependencies.
- `capell.json` declares Mosaic, Forms, and Analytics dependencies.
- `composer.json` provider discovery points to `CampaignsServiceProvider`.

Run:

```bash
vendor/bin/pest packages/campaigns/tests/Arch packages/campaigns/tests/Unit/ManifestRequirementsTest.php
```

Expected: PASS once package files are complete.

- [ ] **Step 2: Add docs**

Document:

- How CampaignGroup, CampaignLandingPage, CampaignCtaBlock, ConversionGoal, and Conversion relate.
- How UTM attribution works with Analytics visits.
- How Forms submissions become conversions.
- How to install campaign layouts.
- How to add a new campaign widget.

- [ ] **Step 3: Add cross-package boot coverage**

Update the shared package tests to register and force-install Campaigns after Mosaic, Forms, Analytics, and SEO Tools.

- [ ] **Step 4: Verify package test suite**

Run:

```bash
vendor/bin/pest packages/campaigns/tests
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/campaigns tests/Packages
git commit -m "test: cover campaigns package integration"
```

## Task 10: Full Verification

**Files:**

- No new files.

- [ ] **Step 1: Run package tests**

```bash
vendor/bin/pest packages/campaigns/tests
```

Expected: PASS.

- [ ] **Step 2: Run affected package tests**

```bash
vendor/bin/pest packages/analytics/tests packages/forms/tests packages/mosaic/tests packages/campaigns/tests
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
git add packages/campaigns composer.json tests/Packages
git commit -m "chore: finalize campaigns package"
```

## Implementation Notes

- Prefer using existing Analytics UTM fields on `analytics_visits`; do not add duplicate visit attribution tables in Campaigns.
- Store conversion-specific snapshots in `campaign_conversions.attribution` so historical reporting survives later campaign edits.
- Do not make campaign landing pages a new Core page type in v1. Link Campaigns records to existing Core pages so Mosaic, SEO Tools, Workspaces, and Navigation keep their current responsibilities.
- Make campaign layout presets installable data, not hard-coded theme behavior.
- CTA blocks are reusable campaign content, while Mosaic widgets decide placement and rendering.
- Every public reporting action should return Data objects or typed collections so dashboard widgets stay thin.
