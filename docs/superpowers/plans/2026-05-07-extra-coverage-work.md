# Extra Coverage Work Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Raise the freshly measured full-suite statement coverage from **65.99%** to **at least 70.00%** using meaningful upstream tests.

**Architecture:** Split work by package so sub-agents have disjoint file ownership and can commit safely. Prefer package-local tests around Actions, controllers, middleware, providers, resource/page behavior, and real model/database flows; do not add direct enum/DTO/component-shape tests just to move coverage.

**Tech Stack:** PHP 8.2, Laravel, Pest, PCOV, Clover XML, Filament, Livewire, Capell package conventions.

---

## Current Baseline

Fresh full run from `coverage/clover.xml`:

- Tests: `1896` total, `1890` passed, `6` skipped.
- Statement coverage: `29412 / 44569 = 65.99%`.
- Needed for 70% at the current denominator: about `1787` additional covered statements.
- Full coverage command:

```bash
mkdir -p coverage
php -d memory_limit=-1 \
  -d pcov.enabled=1 \
  -d pcov.directory=. \
  -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' \
  vendor/bin/pest --coverage --coverage-clover=coverage/clover.xml --colors=always --configuration=phpunit.xml
```

Package-level coverage command template:

```bash
php -d memory_limit=-1 \
  -d pcov.enabled=1 \
  -d pcov.directory=. \
  -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' \
  vendor/bin/pest packages/{package}/tests --coverage --coverage-clover=coverage/{package}-clover.xml --colors=always --configuration=phpunit.xml
```

Parse remaining zero-covered source files for a package:

```bash
php -r '$xml=simplexml_load_file("coverage/{package}-clover.xml"); $rows=[]; foreach ($xml->xpath("//file") as $file) { $path=(string)$file["name"]; if (!str_contains($path, "/packages/{package}/src/")) continue; $metrics=$file->metrics; $statements=(int)$metrics["statements"]; $covered=(int)$metrics["coveredstatements"]; if ($statements > 0 && $covered === 0) $rows[]=[$statements, str_replace(getcwd()."/", "", $path)]; } usort($rows, fn($first,$second)=>[-$first[0], $first[1]] <=> [-$second[0], $second[1]]); foreach ($rows as $row) echo $row[0]."\t".$row[1]."\n";'
```

## Global Rules For All Sub-Agents

- Before editing, run `git status --short`. Preserve unrelated work, especially the current unrelated navigation changes:
    - `packages/navigation/resources/views/components/page/navigations.blade.php`
    - `packages/navigation/tests/Feature/Filament/Resources/Page/`
- Each sub-agent owns only the package paths assigned in its task.
- Do not modify source code unless a test exposes a real defect and the fix is package-local.
- Do not add tests that only assert enum cases, DTO constructor storage, translation strings, or Filament component arrays.
- Acceptable UI coverage must drive real behavior: resource pages render, Livewire actions run, form submission persists or validates, table queries return expected rows, controller responses enforce behavior.
- Use `vendor/bin/pest`, not `php artisan`.
- Run the narrowest focused Pest command first, then the package-level command, then package-level coverage.
- Commit after each package batch. Commit only files in the owned package.
- Commit message format: `test: cover {package} {behavior}`.

## Coordination Map

These workstreams can run in parallel because their write sets are disjoint:

| Workstream | Package               | Write Scope                             | Expected Covered Statement Gain |
| ---------- | --------------------- | --------------------------------------- | ------------------------------: |
| A          | `media-library`       | `packages/media-library/tests/**`       |                         250-350 |
| B          | `publishing-studio`   | `packages/publishing-studio/tests/**`   |                         350-550 |
| C          | `frontend-authoring`  | `packages/frontend-authoring/tests/**`  |                         100-160 |
| D          | `login-audit`         | `packages/login-audit/tests/**`         |                          80-140 |
| E          | `migration-assistant` | `packages/migration-assistant/tests/**` |                          80-130 |
| F          | `redirects`           | `packages/redirects/tests/**`           |                          70-120 |
| G          | `newsletter`          | `packages/newsletter/tests/**`          |                         150-250 |
| H          | `layout-builder`      | `packages/layout-builder/tests/**`      |                         300-500 |
| J          | `seo-suite`           | `packages/seo-suite/tests/**`           |                         300-500 |

Do A-G first. If the full report is still below 70%, continue with H-J. H-J contain many large Filament/configurator files, so tests must be through existing higher-level UI/resource behavior or rendered widget behavior, not component tree snapshots.

---

## Task A: Media Library Migration And Health Coverage

**Files:**

- Modify: `packages/media-library/tests/Integration/MigrateSpatieToCuratorCommandTest.php`
- Create or modify: `packages/media-library/tests/Integration/MediaHealthTest.php`
- Do not modify: `packages/media-library/src/**` unless a real bug is proven.

**Targets from Clover:**

- `packages/media-library/src/Actions/MigrateSpatieMediaToCuratorAction.php` (`199` zero statements)
- `packages/media-library/src/Actions/DashboardReports/BuildMediaHealthQueryAction.php` (`87` zero statements)
- `packages/media-library/src/Concerns/InteractsWithCuratorMedia.php` (`43` zero statements)
- `packages/media-library/src/Console/MigrateSpatieToCuratorCommand.php` (`37` zero statements)

- [ ] **Step 1: Inspect existing tests and source**

Run:

```bash
sed -n '1,260p' packages/media-library/tests/Integration/MigrateSpatieToCuratorCommandTest.php
sed -n '1,260p' packages/media-library/tests/Integration/MediaHealthTest.php
sed -n '1,360p' packages/media-library/src/Actions/MigrateSpatieMediaToCuratorAction.php
sed -n '1,220p' packages/media-library/src/Actions/DashboardReports/BuildMediaHealthQueryAction.php
```

Expected: identify existing helper `seedSpatieFixture()` and the in-memory `media`, `curator`, and `test_curator_owners` tables.

- [ ] **Step 2: Add migration behavior tests for meaningful branches**

Add tests that cover:

- Existing Curator row reuse updates owner FK without creating duplicate media.
- Owner FK is not overwritten when already populated.
- Unknown owner class produces a warning and skips the row.
- Collection without a matching owner FK column produces a warning and skips the row.
- Collection filter and owner type filter limit processed rows.
- Curations metadata is normalized when Spatie custom properties contain both `{"key": ...}` and `{"curation": {...}}` formats.

Place these in `packages/media-library/tests/Integration/MigrateSpatieToCuratorCommandTest.php`.

- [ ] **Step 3: Add media health query behavior tests**

In `packages/media-library/tests/Integration/MediaHealthTest.php`, cover the query action through real database rows:

- Curator rows missing physical files are returned as health issues.
- Curator rows with existing fake storage files are not returned.
- Rows with owner FK references and rows without references produce the expected health classification if the action supports that distinction.

Use `Storage::fake('public')` already provided by the package test case.

- [ ] **Step 4: Verify focused tests**

Run:

```bash
vendor/bin/pest packages/media-library/tests/Integration/MigrateSpatieToCuratorCommandTest.php packages/media-library/tests/Integration/MediaHealthTest.php --configuration=phpunit.xml
```

Expected: all media-library focused tests pass.

- [ ] **Step 5: Verify package coverage**

Run:

```bash
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest packages/media-library/tests --coverage --coverage-clover=coverage/media-library-clover.xml --colors=always --configuration=phpunit.xml
```

Expected: package tests pass and the migration action/health action are no longer 0%.

- [ ] **Step 6: Commit**

```bash
git status --short
git add packages/media-library/tests
git commit -m "test: cover media library migration health"
```

---

## Task B: Publishing Studio Dashboard And Import Coverage

**Files:**

- Create or modify tests under `packages/publishing-studio/tests/**`.
- Do not modify other packages.

**Targets from Clover:**

- `packages/publishing-studio/src/Actions/Imports/AdvancePageImportToValidationAction.php` (`169`)
- `packages/publishing-studio/src/Actions/Dashboard/BuildContentHealthAction.php` (`91`)
- `packages/publishing-studio/src/Actions/Dashboard/BuildMyWorkQueueAction.php` (`85`)
- `packages/publishing-studio/src/Actions/Imports/StartPageImportAction.php` (`84`)
- `packages/publishing-studio/src/Actions/Dashboard/BuildRecentlyPublishedAction.php` (`44`)
- `packages/publishing-studio/src/Actions/Dashboard/BuildSiteStatsAction.php` (`37`)
- `packages/publishing-studio/src/Actions/Imports/DispatchPageImportAction.php` (`33`)

- [ ] **Step 1: Inspect source and nearest tests**

Run:

```bash
rg --files packages/publishing-studio/src/Actions packages/publishing-studio/tests | sort
sed -n '1,260p' packages/publishing-studio/src/Actions/Imports/AdvancePageImportToValidationAction.php
sed -n '1,220p' packages/publishing-studio/src/Actions/Imports/StartPageImportAction.php
sed -n '1,180p' packages/publishing-studio/src/Actions/Dashboard/BuildContentHealthAction.php
sed -n '1,180p' packages/publishing-studio/src/Actions/Dashboard/BuildMyWorkQueueAction.php
```

- [ ] **Step 2: Cover dashboard Actions through real pages/workspaces**

Add tests under `packages/publishing-studio/tests/Integration/Actions/Dashboard/` for:

- Content health counts the concrete categories returned by `BuildContentHealthAction`, including pages needing review, stale drafts, and scheduled or pending content when those categories exist in the source action.
- My work queue returns only items assigned to or actionable by the current actor.
- Recently published returns published items ordered by publish timestamp.
- Site stats returns real counts from factories, not mocked arrays.

Use the package’s existing test case and factories. Assert IDs/counts/order, not DTO constructor behavior.

- [ ] **Step 3: Cover import Actions through import session fixtures**

Add tests under `packages/publishing-studio/tests/Integration/Actions/Imports/` for:

- Starting an import creates the import session/review rows from a local fixture package.
- Advancing to validation updates status and materializes validation decisions.
- Dispatching an import queues or marks the expected import work without hitting external services.
- Validation rejects missing/invalid import input with a meaningful status or exception.

- [ ] **Step 4: Verify focused tests**

Run the new files only:

```bash
vendor/bin/pest packages/publishing-studio/tests/Integration/Actions/Dashboard packages/publishing-studio/tests/Integration/Actions/Imports --configuration=phpunit.xml
```

- [ ] **Step 5: Verify package coverage**

```bash
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest packages/publishing-studio/tests --coverage --coverage-clover=coverage/publishing-studio-clover.xml --colors=always --configuration=phpunit.xml
```

- [ ] **Step 6: Commit**

```bash
git status --short
git add packages/publishing-studio/tests
git commit -m "test: cover publishing studio dashboard imports"
```

---

## Task C: Frontend Authoring Safety Coverage

**Files:**

- Create or modify tests under `packages/frontend-authoring/tests/**`.
- Do not modify public frontend Blade unless a security leak is found and proven.

**Targets from Clover:**

- `packages/frontend-authoring/src/Livewire/EditRegionField.php` (`44`)
- `packages/frontend-authoring/src/Actions/ClearAffectedCachedUrlsAction.php` (`28`)
- `packages/frontend-authoring/src/Actions/UpdateEditableRegionAction.php` (`20`)
- `packages/frontend-authoring/src/Data/EditableRegionPayloadData.php` (`19`)
- `packages/frontend-authoring/src/Support/EditableRegionSigner.php` (`19`)
- `packages/frontend-authoring/src/Actions/CollectAffectedCachedUrlsAction.php` (`17`)
- `packages/frontend-authoring/src/Http/Controllers/EditRegionController.php` (`7`)

- [ ] **Step 1: Inspect frontend-authoring tests and source**

```bash
rg --files packages/frontend-authoring/src packages/frontend-authoring/tests | sort
sed -n '1,220p' packages/frontend-authoring/src/Actions/CollectAffectedCachedUrlsAction.php
sed -n '1,220p' packages/frontend-authoring/src/Actions/ClearAffectedCachedUrlsAction.php
sed -n '1,240p' packages/frontend-authoring/src/Actions/UpdateEditableRegionAction.php
sed -n '1,220p' packages/frontend-authoring/src/Support/EditableRegionSigner.php
sed -n '1,180p' packages/frontend-authoring/src/Http/Controllers/EditRegionController.php
```

- [ ] **Step 2: Add action and signer tests**

Cover:

- Collect affected cached URLs from a page/region setup using real factories.
- Clear affected cached URLs calls the cache records expected by the action.
- Update editable region persists only allowed content/fields.
- Signed region URLs validate, reject tampered payloads, and expire if expiration is supported.

- [ ] **Step 3: Add controller safety tests**

Cover:

- Anonymous and non-admin requests cannot update regions.
- Authenticated admin with valid signature can update a region.
- Invalid signature returns forbidden or validation failure.

- [ ] **Step 4: Verify**

```bash
vendor/bin/pest packages/frontend-authoring/tests --configuration=phpunit.xml
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest packages/frontend-authoring/tests --coverage --coverage-clover=coverage/frontend-authoring-clover.xml --colors=always --configuration=phpunit.xml
```

- [ ] **Step 5: Commit**

```bash
git add packages/frontend-authoring/tests
git commit -m "test: cover frontend authoring region safety"
```

---

## Task D: Login Audit Middleware And Dashboard Query Coverage

**Files:**

- Create or modify tests under `packages/login-audit/tests/**`.

**Targets from Clover:**

- `packages/login-audit/src/Http/Middleware/AdminActivityMiddleware.php` (`24`)
- `packages/login-audit/src/Http/Middleware/UserActivityMiddleware.php` (`19`)
- `packages/login-audit/src/Actions/BuildLoginAuditsQueryAction.php` (`5`)
- `packages/login-audit/src/Actions/ResolveLoginAuditIpAddressAction.php` (`5`)
- `packages/login-audit/src/Filament/Widgets/LoginAuditsWidget.php` (`72`) only if covered via widget data/query behavior, not widget shape.

- [ ] **Step 1: Inspect middleware and tests**

```bash
rg --files packages/login-audit/src packages/login-audit/tests | sort
sed -n '1,220p' packages/login-audit/src/Http/Middleware/AdminActivityMiddleware.php
sed -n '1,220p' packages/login-audit/src/Http/Middleware/UserActivityMiddleware.php
sed -n '1,140p' packages/login-audit/src/Actions/BuildLoginAuditsQueryAction.php
sed -n '1,140p' packages/login-audit/src/Actions/ResolveLoginAuditIpAddressAction.php
```

- [ ] **Step 2: Add middleware behavior tests**

Cover:

- Admin request records activity with actor, IP, route/path, and user agent.
- Non-authenticated request is skipped or recorded according to source behavior.
- User middleware updates expected activity fields without overwriting unrelated state.
- IP resolver respects proxy headers only when configured by the action.

- [ ] **Step 3: Add query/widget data test if natural**

Cover `BuildLoginAuditsQueryAction` by seeding audit rows and asserting filters/order. If `LoginAuditsWidget` exposes a data method that calls the query action, instantiate the widget and assert returned records.

- [ ] **Step 4: Verify and commit**

```bash
vendor/bin/pest packages/login-audit/tests --configuration=phpunit.xml
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest packages/login-audit/tests --coverage --coverage-clover=coverage/login-audit-clover.xml --colors=always --configuration=phpunit.xml
git add packages/login-audit/tests
git commit -m "test: cover login audit activity tracking"
```

---

## Task E: Migration Assistant Relation Resolution Coverage

**Files:**

- Create or modify tests under `packages/migration-assistant/tests/**`.

**Targets from Clover:**

- `packages/migration-assistant/src/Actions/BuildRelationResolveRowsAction.php` (`42`)
- `packages/migration-assistant/src/Services/Import/Resolvers/MediaMatchResolver.php` (`17`)
- `packages/migration-assistant/src/Support/AdminPageExporter.php` (`10`)
- `packages/migration-assistant/src/Data/RelationResolveRow.php` (`9`) only through action output.

- [ ] **Step 1: Inspect source and tests**

```bash
rg --files packages/migration-assistant/src packages/migration-assistant/tests | sort
sed -n '1,220p' packages/migration-assistant/src/Actions/BuildRelationResolveRowsAction.php
sed -n '1,180p' packages/migration-assistant/src/Services/Import/Resolvers/MediaMatchResolver.php
sed -n '1,180p' packages/migration-assistant/src/Support/AdminPageExporter.php
```

- [ ] **Step 2: Add relation resolution tests**

Cover:

- Rows are produced for unresolved relation references in an import package.
- Already-resolved relations are omitted or marked resolved according to source behavior.
- Media resolver matches by filename/path/metadata using real fixture data.
- Missing media produces unresolved row with useful label/reason.

- [ ] **Step 3: Verify and commit**

```bash
vendor/bin/pest packages/migration-assistant/tests --configuration=phpunit.xml
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest packages/migration-assistant/tests --coverage --coverage-clover=coverage/migration-assistant-clover.xml --colors=always --configuration=phpunit.xml
git add packages/migration-assistant/tests
git commit -m "test: cover migration assistant relation resolution"
```

---

## Task F: Redirects Action And Policy Coverage

**Files:**

- Create or modify tests under `packages/redirects/tests/**`.

**Targets from Clover:**

- `packages/redirects/src/Actions/RefreshRedirectHealthSnapshotsAction.php` (`12`)
- `packages/redirects/src/Support/FrontendRedirectResolver.php` (`5`)
- `packages/redirects/src/Policies/RedirectPolicy.php` (`25`)
- `packages/redirects/src/Filament/Exports/RedirectExporter.php` (`34`) only if covered through export behavior.

- [ ] **Step 1: Inspect source and tests**

```bash
rg --files packages/redirects/src packages/redirects/tests | sort
sed -n '1,180p' packages/redirects/src/Actions/RefreshRedirectHealthSnapshotsAction.php
sed -n '1,160p' packages/redirects/src/Support/FrontendRedirectResolver.php
sed -n '1,180p' packages/redirects/src/Policies/RedirectPolicy.php
```

- [ ] **Step 2: Add redirect behavior tests**

Cover:

- Frontend resolver returns the expected redirect decision for exact source URL.
- Resolver ignores inactive/expired redirect records if supported.
- Health snapshot refresh creates/updates stale/broken redirect snapshots.
- Policy allows and denies users based on roles/permissions used by this package.

- [ ] **Step 3: Verify and commit**

```bash
vendor/bin/pest packages/redirects/tests --configuration=phpunit.xml
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest packages/redirects/tests --coverage --coverage-clover=coverage/redirects-clover.xml --colors=always --configuration=phpunit.xml
git add packages/redirects/tests
git commit -m "test: cover redirects resolution health"
```

---

## Task G: Newsletter Segment And Resource Behavior Coverage

**Files:**

- Create or modify tests under `packages/newsletter/tests/**`.

**Targets from Clover:**

- `packages/newsletter/src/Actions/EvaluateNewsletterSegmentAction.php` (`24`)
- `packages/newsletter/src/Filament/Resources/*` large resource classes, only through resource/page behavior.

- [ ] **Step 1: Inspect newsletter source and test conventions**

```bash
rg --files packages/newsletter/src packages/newsletter/tests | sort
sed -n '1,220p' packages/newsletter/src/Actions/EvaluateNewsletterSegmentAction.php
```

- [ ] **Step 2: Add segment action tests**

Cover:

- Segment with matching rules includes expected subscribers.
- Segment with non-matching rules excludes subscribers.
- Compound rules behave according to source logic.
- Empty/invalid rule payload fails safely according to source behavior.

- [ ] **Step 3: Add resource behavior tests only where useful**

For resources such as Subscribers, Segments, Provider Connections, and Import Batches:

- Use Filament/Livewire resource page tests that already exist as a pattern in this repo.
- Assert page renders and table contains seeded records.
- Assert create/edit actions persist real models.
- Do not assert static navigation labels or resource metadata only.

- [ ] **Step 4: Verify and commit**

```bash
vendor/bin/pest packages/newsletter/tests --configuration=phpunit.xml
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest packages/newsletter/tests --coverage --coverage-clover=coverage/newsletter-clover.xml --colors=always --configuration=phpunit.xml
git add packages/newsletter/tests
git commit -m "test: cover newsletter segment behavior"
```

---

## Task H: Layout Builder Upstream UI Coverage

**Files:**

- Create or modify tests under `packages/layout-builder/tests/**`.

**Targets from Clover:**

- `packages/layout-builder/src/Filament/Resources/Widgets/Tables/WidgetAssetsTable.php` (`156`)
- `packages/layout-builder/src/Filament/Components/Forms/WidgetSelect.php` (`78`)
- `packages/layout-builder/src/Filament/Components/Forms/CarouselSettingsSchema.php` (`48`)
- Multiple `Modern*Configurator.php` files.

- [ ] **Step 1: Find existing resource/page/rendering tests**

```bash
rg "WidgetAssetsTable|WidgetSelect|ModernHero|ModernCardGrid|CarouselSettings" packages/layout-builder/tests packages/layout-builder/src -n
rg --files packages/layout-builder/tests/Feature packages/layout-builder/tests/Integration | sort
```

- [ ] **Step 2: Cover WidgetAssetsTable through relation manager/resource behavior**

Add or extend a test that:

- Creates a widget with media/assets.
- Opens the resource/relation manager/table path used by the app.
- Asserts seeded assets appear.
- Runs attach/detach/reorder actions if those are exposed.

- [ ] **Step 3: Cover WidgetSelect through a real form workflow**

Add or extend a test that:

- Opens the page/layout/widget form where `WidgetSelect` is used.
- Creates/selects a widget through the form.
- Persists the selected widget relation or form state to the database.

- [ ] **Step 4: Cover configurators through rendered widget output**

Only cover configurators when the test renders a widget/page and asserts visible output:

- Modern hero banner renders heading, image/media, CTA.
- Modern card grid renders all cards and links.
- Modern CTA section renders the configured action URL.

Do not assert the returned Filament fields directly.

- [ ] **Step 5: Verify and commit**

```bash
vendor/bin/pest packages/layout-builder/tests --configuration=phpunit.xml
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest packages/layout-builder/tests --coverage --coverage-clover=coverage/layout-builder-clover.xml --colors=always --configuration=phpunit.xml
git add packages/layout-builder/tests
git commit -m "test: cover layout builder widget workflows"
```

---

## Task I: Block Library Upstream UI Coverage

**Files:**

**Targets from Clover:**

- Other block configurators.

- [ ] **Step 1: Find existing rendering and resource tests**

```bash

```

Add or extend a resource test that:

- Seeds content blocks with different statuses/types/sites.
- Opens the list/resource page.
- Asserts table shows expected rows and filters/search behave.
- Runs a table action if source exposes meaningful actions.

- [ ] **Step 3: Cover ContentSelect and repeaters through create/edit workflows**

Add or extend a test that:

- Uses the Filament create/edit page where content selection is used.
- Creates content with assets/actions through the form.
- Persists related assets/actions and validates required fields.

- [ ] **Step 4: Cover configurators through frontend rendering**

Add rendered-output tests for popular/hero/default/testimonial content blocks:

- Create a content block using the configurator-backed type.
- Render the public/frontend Blade or Livewire path already used by existing tests.
- Assert visible headings/body/actions/assets.

Do not assert raw configurator schema arrays.

- [ ] **Step 5: Verify and commit**

```bash
git commit -m "test: cover block library workflows"
```

---

## Task J: SEO Suite Admin And Report Coverage

**Files:**

- Create or modify tests under `packages/seo-suite/tests/**`.

**Targets from Clover:**

- `packages/seo-suite/src/Support/Admin/PageTitleWithSlugInputExtender.php` (`130`)
- `packages/seo-suite/src/Support/Admin/SearchMetaDataSectionExtender.php` (`125`)
- `packages/seo-suite/src/Support/Admin/PageContentEditorConfigurator.php` (`101`)
- `packages/seo-suite/src/Support/Publishing/SeoPublishReportProviderAdapter.php` (`62`)
- `packages/seo-suite/src/Filament/Actions/AiCreatorAction.php` (`174`) only if external AI is faked and behavior is asserted.

- [ ] **Step 1: Inspect source and existing seo-suite tests**

```bash
rg --files packages/seo-suite/src packages/seo-suite/tests | sort
sed -n '1,220p' packages/seo-suite/src/Support/Admin/PageTitleWithSlugInputExtender.php
sed -n '1,220p' packages/seo-suite/src/Support/Admin/SearchMetaDataSectionExtender.php
sed -n '1,220p' packages/seo-suite/src/Support/Admin/PageContentEditorConfigurator.php
sed -n '1,180p' packages/seo-suite/src/Support/Publishing/SeoPublishReportProviderAdapter.php
```

- [ ] **Step 2: Cover admin extenders through page/resource forms**

Add tests that:

- Open or build the page form path where SEO extenders are registered.
- Fill title/slug/meta fields.
- Assert page translations/meta persist correctly.
- Assert validation or derived slug behavior through saved model state.

- [ ] **Step 3: Cover publish report adapter through real page snapshots**

Add an integration test that:

- Creates pages/translations with complete and incomplete SEO metadata.
- Runs the publish report provider adapter.
- Asserts issue keys/counts/severity from the resulting report.

- [ ] **Step 4: Cover AI actions only if already fakeable**

If `AiCreatorAction` and `AiImageGeneratorAction` can be faked through existing interfaces:

- Bind fake AI client/provider in the container.
- Execute the Filament action or underlying action path.
- Assert draft content/image metadata is produced and persisted.

If they require live external services or brittle Filament action internals, skip with the reason in the final handoff.

- [ ] **Step 5: Verify and commit**

```bash
vendor/bin/pest packages/seo-suite/tests --configuration=phpunit.xml
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest packages/seo-suite/tests --coverage --coverage-clover=coverage/seo-suite-clover.xml --colors=always --configuration=phpunit.xml
git add packages/seo-suite/tests
git commit -m "test: cover seo suite admin reports"
```

---

## Final Integration Task

Run this after several package commits, especially after A-G, and again after H-J if needed.

- [ ] **Step 1: Rerun full coverage**

```bash
mkdir -p coverage
php -d memory_limit=-1 -d pcov.enabled=1 -d pcov.directory=. -d pcov.exclude='~vendor|tests|storage|bootstrap|.temp~' vendor/bin/pest --coverage --coverage-clover=coverage/clover.xml --colors=always --configuration=phpunit.xml
```

Expected:

- Tests pass except known skipped tests.
- Pest exits `0` if coverage threshold is met.
- If Pest exits `1`, parse Clover before assuming test failure.

- [ ] **Step 2: Parse full Clover totals**

```bash
php -r '$xml=simplexml_load_file("coverage/clover.xml"); $metrics=$xml->project->metrics; $statements=(int)$metrics["statements"]; $coveredStatements=(int)$metrics["coveredstatements"]; printf("statements=%d covered=%d pct=%.2f%%\n", $statements, $coveredStatements, $statements > 0 ? ($coveredStatements / $statements * 100) : 0);'
```

Expected: `pct >= 70.00%`.

- [ ] **Step 3: Parse remaining zero-covered ranked files**

```bash
php -r '$xml=simplexml_load_file("coverage/clover.xml"); $rows=[]; foreach ($xml->xpath("//file") as $file) { $path=(string)$file["name"]; if (!str_contains($path,"/packages/")) continue; $metrics=$file->metrics; $statements=(int)$metrics["statements"]; $covered=(int)$metrics["coveredstatements"]; if ($statements > 0 && $covered === 0) $rows[]=[$statements,str_replace(getcwd()."/","",$path)]; } usort($rows, fn($first,$second)=>[-$first[0],$first[1]] <=> [-$second[0],$second[1]]); foreach(array_slice($rows,0,80) as $row) echo $row[0]."\t".$row[1]."\n";'
```

- [ ] **Step 4: Commit final plan/status updates if any**

If only tests were added and each package already has its own commit, do not create a catch-all commit. If this plan file changed during execution, commit it separately:

```bash
git add docs/superpowers/plans/2026-05-07-extra-coverage-work.md
git commit -m "docs: plan extra package coverage work"
```

## Skip Log Format

Every sub-agent final message must include skipped files with reasons using this format:

```text
Skipped:
- packages/example/src/Enums/ExampleEnum.php: direct enum-value coverage would repeat PHP enum behavior; no upstream behavior path found in this package.
- packages/example/src/Filament/Resources/Example/Schemas/ExampleForm.php: only direct component-array assertions available; no existing resource form flow uses it safely.
```

Do not mark a file skipped just because setup is hard. Skip only when the resulting test would be shallow, brittle, or unrelated to real package behavior.

## Completion Criteria

- Full Clover statement coverage is `>= 70.00%`.
- Full test suite still passes, allowing existing skipped tests.
- Each package batch has its own commit.
- No unrelated navigation changes were staged or reverted.
- Final handoff lists:
    - coverage before/after,
    - package commits,
    - remaining high-statement zero files,
    - skipped rationale.
