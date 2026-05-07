# Admin Navigation Restructure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reorganize the Capell admin sidebar into the approved top-level groups, keep `Dashboard` at the top, place `Marketing` above `Site`, and fix the missing `Events` group icon.

**Architecture:** Keep the change narrow. Adjust navigation group labels where resources and pages declare them, update provider-level group registration order where packages create top-level groups, and trace the admin sidebar group rendering path so the `Events` icon is fixed at the actual integration point rather than papered over in package code.

**Tech Stack:** PHP 8.2, Laravel, Filament, Pest, Capell package service providers/resources/pages.

---

### Task 1: Audit the Admin Navigation Integration Point

**Files:**

- Modify: `docs/superpowers/specs/2026-05-07-admin-navigation-restructure-design.md` only if the audit reveals a design mismatch
- Inspect: `packages/*/src/Providers/*ServiceProvider.php`
- Inspect: admin package files that build sidebar navigation groups once located with `rg "registerNavigationGroup|navigation group|group icon|getNavigationGroup"`
- Test: existing admin/navigation-related Pest tests once identified

- [ ] **Step 1: Locate the code that builds sidebar navigation groups**

Run:

```bash
rg -n "registerNavigationGroup|group icon|getNavigationGroup|navigation groups|Sidebar" packages ../capell-4 -g '*.php'
```

Expected: file paths showing where Capell or the app converts registered groups into Filament sidebar entries.

- [ ] **Step 2: Capture the exact icon rendering behavior**

Read the located files and note whether group icons are only rendered for built-in groups, only for collapsible groups with children, or only when a specific DTO field is populated. Record the relevant class/method names in working notes before editing code.

- [ ] **Step 3: Find an existing passing example of a visible custom group icon**

Search for one custom top-level group that already shows an icon in the UI and compare its registration/render path with `capell-events::generic.events`.

```bash
rg -n "registerNavigationGroup\(" packages -g '*.php'
```

Expected: `campaign-studio`, `newsletter`, and `events` group registrations for comparison.

- [ ] **Step 4: Add a focused failing test around group icon resolution if the admin integration point is testable**

Use the nearest existing admin/navigation test file. Add a test shaped like:

```php
it('keeps the registered events navigation group icon available to the sidebar builder', function (): void {
    $group = /* resolve or build the events group through the admin navigation integration */;

    expect($group->getIcon())->toBe(Filament\Support\Icons\Heroicon::OutlinedCalendar);
});
```

If no integration test seam exists, skip test creation and note that the behavior must be verified through a narrower rendering test or manual app verification later in the plan.

- [ ] **Step 5: Run the narrowest test or static check for the chosen seam**

Run either the single test file you changed or the smallest relevant package suite, for example:

```bash
vendor/bin/pest packages/events/tests --configuration=phpunit.xml
```

Expected: either a failing new test proving the gap, or a note that no direct seam exists and the task proceeds to code inspection-based implementation.

- [ ] **Step 6: Commit the audit scaffolding if code or tests were added**

```bash
git add docs/superpowers/specs/2026-05-07-admin-navigation-restructure-design.md packages
git commit -m "test: capture admin navigation group icon behavior"
```

If this task produced no file changes, leave the repository untouched.

### Task 2: Move Content-Facing Resources Into Content

**Files:**

- Modify: `packages/blog/src/Providers/AdminServiceProvider.php`
- Modify: `packages/events/src/Filament/Pages/EventCalendarPage.php`
- Modify: `packages/events/src/Filament/Resources/Events/EventResource.php`
- Modify: `packages/events/src/Filament/Resources/Occurrences/EventOccurrenceResource.php`
- Modify: `packages/events/src/Filament/Resources/Registrations/EventRegistrationResource.php`
- Modify: `packages/events/src/Filament/Resources/Venues/EventVenueResource.php`
- Modify: `packages/content-sections/src/Providers/ContentSectionsServiceProvider.php`
- Modify: `packages/tags/src/Providers/AdminServiceProvider.php`
- Modify: `packages/publishing-studio/src/Filament/Pages/ScheduledPublishingPage.php`
- Modify: `packages/publishing-studio/src/Filament/Resources/PreviewLinks/PreviewLinkResource.php`
- Test: package tests that already assert navigation group labels for blog/events/publishing-studio if present

- [ ] **Step 1: Write failing tests for the moved content groups where tests already exist**

Add or extend tests with expectations shaped like:

```php
expect(\Capell\Events\Filament\Resources\Events\EventResource::getNavigationGroup())
    ->toBe('Content');

expect(\Capell\PublishingStudio\Filament\Pages\ScheduledPublishingPage::getNavigationGroup())
    ->toBe('Content');
```

Prefer existing test files under:

```text
packages/events/tests/
packages/publishing-studio/tests/
packages/blog/tests/
```

- [ ] **Step 2: Run the targeted tests to see them fail**

Run:

```bash
vendor/bin/pest packages/events/tests packages/publishing-studio/tests packages/blog/tests --configuration=phpunit.xml
```

Expected: failures showing old group labels such as `Events` or `Page`.

- [ ] **Step 3: Update resource/page group methods and provider registrations**

Make the minimal edits so content-facing entries resolve to `Content`. Typical edits will look like:

```php
public static function getNavigationGroup(): ?string
{
    return 'Content';
}
```

And provider resource registration changes such as:

```php
CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
    class: ResourceEnum::Article->value,
    group: 'Content',
    name: strtolower(ResourceEnum::Article->name),
));
```

- [ ] **Step 4: Re-run the targeted tests**

Run:

```bash
vendor/bin/pest packages/events/tests packages/publishing-studio/tests packages/blog/tests --configuration=phpunit.xml
```

Expected: PASS for the changed assertions and no regressions in those packages.

- [ ] **Step 5: Commit the content regrouping**

```bash
git add packages/blog/src/Providers/AdminServiceProvider.php \
packages/events/src/Filament/Pages/EventCalendarPage.php \
packages/events/src/Filament/Resources/Events/EventResource.php \
packages/events/src/Filament/Resources/Occurrences/EventOccurrenceResource.php \
packages/events/src/Filament/Resources/Registrations/EventRegistrationResource.php \
packages/events/src/Filament/Resources/Venues/EventVenueResource.php \
packages/content-sections/src/Providers/ContentSectionsServiceProvider.php \
packages/tags/src/Providers/AdminServiceProvider.php \
packages/publishing-studio/src/Filament/Pages/ScheduledPublishingPage.php \
packages/publishing-studio/src/Filament/Resources/PreviewLinks/PreviewLinkResource.php \
packages/events/tests packages/publishing-studio/tests packages/blog/tests
git commit -m "feat: group content navigation together"
```

### Task 3: Move Campaign and Newsletter Above Site Under Marketing

**Files:**

- Modify: `packages/campaign-studio/src/Providers/AdminServiceProvider.php`
- Modify: `packages/newsletter/src/Providers/AdminServiceProvider.php`
- Modify: campaign/newsletter resources that hardcode their `getNavigationGroup()` label
- Test: `packages/campaign-studio/tests/` and `packages/newsletter/tests/` if they contain navigation assertions

- [ ] **Step 1: Add failing assertions for the approved `Marketing` group label**

Use existing tests or add a small focused one with expectations shaped like:

```php
expect(\Capell\CampaignStudio\Filament\Resources\CampaignGroups\CampaignGroupResource::getNavigationGroup())
    ->toBe('Marketing');
```

- [ ] **Step 2: Run the relevant package tests to verify failure**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests packages/newsletter/tests --configuration=phpunit.xml
```

Expected: failures if old labels still resolve to package-specific groups.

- [ ] **Step 3: Replace package-specific group labels with `Marketing` and preserve ordering**

Update provider and resource code so:

```php
CapellAdmin::registerNavigationGroup(
    label: 'Marketing',
    icon: Heroicon::OutlinedMegaphone,
    position: NavigationGroupPositionEnum::After,
    relativeTo: 'Content',
);
```

Or, if the admin group API expects translation keys rather than literal labels, add/use a stable translation-backed `Marketing` label consistently across both packages.

Set newsletter to resolve after marketing rather than creating a separate top-level package group.

- [ ] **Step 4: Re-run the targeted marketing tests**

Run:

```bash
vendor/bin/pest packages/campaign-studio/tests packages/newsletter/tests --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 5: Commit the marketing regrouping**

```bash
git add packages/campaign-studio/src/Providers/AdminServiceProvider.php \
packages/newsletter/src/Providers/AdminServiceProvider.php \
packages/campaign-studio/tests packages/newsletter/tests
git commit -m "feat: group campaign and newsletter under marketing"
```

### Task 4: Collapse Structure Into Site

**Files:**

- Modify: `packages/address/src/Filament/Resources/Addresses/AddressResource.php` only if it participates in the sidebar
- Modify: `packages/address/src/Filament/Resources/Countries/CountryResource.php` only if it participates in the sidebar
- Modify: `packages/navigation/src/Providers/NavigationServiceProvider.php`
- Modify: `packages/navigation/src/Filament/Resources/Navigations/NavigationResource.php`
- Modify: `packages/layout-builder/src/Providers/LayoutBuilderServiceProvider.php`
- Modify: `packages/layout-builder/src/Filament/Resources/Widgets/WidgetResource.php`
- Modify: resource/page classes for Sites, Languages, Types, Themes, Redirects once located
- Modify: `packages/redirects/src/Providers/RedirectsServiceProvider.php`
- Test: package tests that assert `Structure` or existing group labels

- [ ] **Step 1: Inventory every resource currently under `Structure`**

Run:

```bash
rg -n "return .*Structure|group: 'Navigation'|group: 'Redirect'|ResourceEnum::Layout|ResourceEnum::Widget" packages -g '*.php'
```

Expected: the exact resource/page files that must move to `Site`.

- [ ] **Step 2: Add failing expectations for `Site`**

Add assertions shaped like:

```php
expect(\Capell\Navigation\Filament\Resources\Navigations\NavigationResource::getNavigationGroup())
    ->toBe('Site');
```

- [ ] **Step 3: Run the targeted site-structure tests to verify failure**

Run:

```bash
vendor/bin/pest packages/navigation/tests packages/layout-builder/tests packages/redirects/tests --configuration=phpunit.xml
```

Expected: failing assertions or no-op if a package lacks direct tests, in which case add the smallest test file that can assert the label.

- [ ] **Step 4: Update provider/resource group names from structure-specific values to `Site`**

Typical provider edits:

```php
CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
    class: NavigationResource::class,
    group: 'Site',
));
```

Typical resource edits:

```php
public static function getNavigationGroup(): ?string
{
    return 'Site';
}
```

- [ ] **Step 5: Re-run the site tests**

Run:

```bash
vendor/bin/pest packages/navigation/tests packages/layout-builder/tests packages/redirects/tests --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 6: Commit the site regrouping**

```bash
git add packages/navigation/src/Providers/NavigationServiceProvider.php \
packages/navigation/src/Filament/Resources/Navigations/NavigationResource.php \
packages/layout-builder/src/Providers/LayoutBuilderServiceProvider.php \
packages/layout-builder/src/Filament/Resources/Widgets/WidgetResource.php \
packages/redirects/src/Providers/RedirectsServiceProvider.php \
packages/navigation/tests packages/layout-builder/tests packages/redirects/tests
git commit -m "feat: rename structure navigation to site"
```

### Task 5: Move Reporting and Operations Into Insights and System

**Files:**

- Modify: `packages/insights/src/Filament/Pages/InsightsPage.php`
- Modify: `packages/ga4-reports/src/Filament/Pages/GA4ReportsPage.php`
- Modify: `packages/seo-suite/src/Filament/Pages/SeoAuditPage.php`
- Modify: `packages/seo-suite/src/Filament/Pages/NotFoundUrlsPage.php`
- Modify: `packages/seo-suite/src/Filament/Pages/BrokenLinksPage.php`
- Modify: `packages/seo-suite/src/Filament/Pages/TranslationCoveragePage.php`
- Modify: `packages/diagnostics/src/Filament/Pages/*.php`
- Modify: `packages/deployments/src/Filament/Pages/DeploymentConnectionPage.php`
- Modify: `packages/login-audit/src/Filament/Resources/LoginAudits/LoginAuditResource.php`
- Modify: `packages/migration-assistant/src/Filament/Resources/ImportSessions/ImportSessionResource.php`
- Test: relevant package tests that assert `Monitoring`, `Administration`, or existing labels

- [ ] **Step 1: Add failing expectations for `Insights` and `System`**

Examples:

```php
expect(\Capell\GA4Reports\Filament\Pages\GA4ReportsPage::getNavigationGroup())
    ->toBe('Insights');

expect(\Capell\Deployments\Filament\Pages\DeploymentConnectionPage::getNavigationGroup())
    ->toBe('System');
```

- [ ] **Step 2: Run the targeted tests to verify failure**

Run:

```bash
vendor/bin/pest packages/ga4-reports/tests packages/seo-suite/tests packages/deployments/tests packages/diagnostics/tests --configuration=phpunit.xml
```

Expected: failures on old labels such as `Monitoring` or `Administration`.

- [ ] **Step 3: Update the page/resource group labels**

Make direct group returns explicit:

```php
public static function getNavigationGroup(): ?string
{
    return 'Insights';
}
```

and:

```php
public static function getNavigationGroup(): ?string
{
    return 'System';
}
```

- [ ] **Step 4: Re-run the targeted tests**

Run:

```bash
vendor/bin/pest packages/ga4-reports/tests packages/seo-suite/tests packages/deployments/tests packages/diagnostics/tests --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 5: Commit the insights/system regrouping**

```bash
git add packages/insights/src/Filament/Pages/InsightsPage.php \
packages/ga4-reports/src/Filament/Pages/GA4ReportsPage.php \
packages/seo-suite/src/Filament/Pages/SeoAuditPage.php \
packages/seo-suite/src/Filament/Pages/NotFoundUrlsPage.php \
packages/seo-suite/src/Filament/Pages/BrokenLinksPage.php \
packages/seo-suite/src/Filament/Pages/TranslationCoveragePage.php \
packages/diagnostics/src/Filament/Pages \
packages/deployments/src/Filament/Pages/DeploymentConnectionPage.php \
packages/login-audit/src/Filament/Resources/LoginAudits/LoginAuditResource.php \
packages/migration-assistant/src/Filament/Resources/ImportSessions/ImportSessionResource.php \
packages/ga4-reports/tests packages/seo-suite/tests packages/deployments/tests packages/diagnostics/tests
git commit -m "feat: regroup insights and system navigation"
```

### Task 6: Fix the Events Group Icon at the Real Sidebar Seam

**Files:**

- Modify: the admin integration file located in Task 1 that translates registered groups into sidebar items
- Modify: `packages/events/src/Providers/EventsServiceProvider.php` only if the integration expects a different icon contract
- Test: the focused admin/navigation test file created or updated in Task 1

- [ ] **Step 1: Write or enable the failing icon test**

Use the exact seam found in Task 1. Keep the expectation concrete:

```php
expect($eventsGroup->getIcon())->toBe(Filament\Support\Icons\Heroicon::OutlinedCalendar);
```

- [ ] **Step 2: Run that test and confirm the failure**

Run the narrowest command for the changed test file, for example:

```bash
vendor/bin/pest path/to/navigation/icon-test.php --configuration=phpunit.xml
```

Expected: FAIL, proving the icon is being dropped or never mapped.

- [ ] **Step 3: Implement the minimal fix in the sidebar group builder**

Adjust the code so the registered group icon survives into the actual Filament navigation item. The final code should preserve existing behavior for all other groups and avoid special-casing `Events`.

If the fix belongs in a DTO mapper, the edit should resemble:

```php
$navigationGroup = NavigationGroup::make($resolvedLabel)
    ->icon($registeredGroup->icon)
    ->collapsed();
```

Use the actual local class and API names found in Task 1 rather than inventing new abstractions.

- [ ] **Step 4: Re-run the icon test and the Events package tests**

Run:

```bash
vendor/bin/pest path/to/navigation/icon-test.php packages/events/tests --configuration=phpunit.xml
```

Expected: PASS.

- [ ] **Step 5: Commit the icon fix**

```bash
git add packages/events/src/Providers/EventsServiceProvider.php path/to/admin/navigation/file.php path/to/test.php
git commit -m "fix: preserve custom navigation group icons"
```

### Task 7: End-to-End Verification and Cleanup

**Files:**

- Modify: any touched test files that still need assertion cleanup
- Verify: all changed packages only

- [ ] **Step 1: Run the full set of changed package suites**

Run:

```bash
vendor/bin/pest \
packages/events/tests \
packages/blog/tests \
packages/content-sections/tests \
packages/tags/tests \
packages/publishing-studio/tests \
packages/campaign-studio/tests \
packages/newsletter/tests \
packages/navigation/tests \
packages/layout-builder/tests \
packages/redirects/tests \
packages/ga4-reports/tests \
packages/seo-suite/tests \
packages/deployments/tests \
packages/diagnostics/tests \
packages/insights/tests \
packages/login-audit/tests \
packages/migration-assistant/tests \
--configuration=phpunit.xml
```

Expected: all changed package suites pass.

- [ ] **Step 2: If a local demo admin is available, verify the sidebar manually**

Run:

```bash
composer serve
```

Then verify:

```text
Dashboard
Content
Marketing
Site
Insights
System
```

And confirm the `Events` group shows its calendar icon.

- [ ] **Step 3: Run a final diff review scoped to touched files**

Run:

```bash
git diff -- packages/events packages/blog packages/content-sections packages/tags packages/publishing-studio packages/campaign-studio packages/newsletter packages/navigation packages/layout-builder packages/redirects packages/ga4-reports packages/seo-suite packages/deployments packages/diagnostics packages/insights packages/login-audit packages/migration-assistant docs/superpowers
```

Expected: only navigation regrouping, tests, and the icon integration fix.

- [ ] **Step 4: Commit any final cleanup**

```bash
git add packages docs/superpowers
git commit -m "chore: finalize admin navigation restructure"
```

## Self-Review

- Spec coverage: the plan covers the approved top-level ordering (`Dashboard`, `Content`, `Marketing`, `Site`, `Insights`, `System`), the content/site/marketing regrouping, and the Events icon fix.
- Placeholder scan: no `TODO`/`TBD` placeholders remain; the one intentional `path/to/...` reference is constrained to the admin integration seam that must be discovered in Task 1 before code is changed.
- Type consistency: all code snippets use existing Filament/Capell patterns already present in the repo, and later tasks reuse the same target labels consistently.
