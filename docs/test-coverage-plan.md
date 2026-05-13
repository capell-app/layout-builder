# Test Coverage & Quality Plan — `capell-packages-4`

This plan complements [`docs/test-plan-actions-services.md`](test-plan-actions-services.md), which covers Core/Admin and historical AI planning now superseded by SEO Suite. This document is scoped to **the optional add-on packages** in this repo and focuses on (a) raising meaningful coverage where it is thinnest, (b) making the suite a faster signal of regressions, and (c) catching cross-package install conflicts that single-package suites cannot see.

## 1. Where we are today

PHPStan: green (level 1 across `packages` + `tests`).

Pest: 1208 / 1458 passing. The 83 failures group into a small number of root causes that pre-date this plan and are tracked separately:

| Root cause                                                                                                         | Count | Where                                                                                   |
| ------------------------------------------------------------------------------------------------------------------ | ----- | --------------------------------------------------------------------------------------- |
| `RenderHookRegistry` short-name lookup in compiled blade (vendor `capell-app/frontend` + `capell-app/admin` views) | ~72   | layout-builder + blog + publishing-studio frontend tests rendering pages                |
| Blog article ordering returning unexpected order in PageLoader                                                     | 4     | `blog/tests/Integration/Loader/PageLoaderArticleOrderingTest`                           |
| Workspace cache not invalidated after `PageSavedAction` / `ReplicatePageAction`                                    | 2     | `publishing-studio/tests/Admin/Feature/Dashboard/CacheInvalidationTest`                 |
| Workspace draft/publish action visibility on `EditPage`                                                            | 2     | `publishing-studio/tests/Feature/EndToEnd/PageDraftPublishFlowTest`                     |
| Misc snapshot / DOM / cookie expectations                                                                          | 3     | `seo-suite` sitemap snapshot, `themes/agency` accessibility, `publishing-studio` cookie |

These are listed so the next pass has a punch list, not as new work for this plan.

### Test count vs source count, per package

Skewed ratios are the first place to look for missing coverage:

| Package            | tests | src |   tests |
| ------------------ | ----: | --: | ------: |
| seo-suite          |    14 | 127 | 0.11 ⚠️ |
| form-builder       |     1 |   3 |    0.33 |
| layout-builder     |    85 | 207 |    0.41 |
| plugins            |    18 |  44 |    0.41 |
| media-library      |     4 |   8 |    0.50 |
| tags               |     8 |  16 |    0.50 |
| blog               |    40 |  71 |    0.56 |
| navigation         |    27 |  42 |    0.64 |
| frontend-authoring |     2 |   3 |    0.67 |
| address            |    19 |  28 |    0.68 |
| publishing-studio  |   100 | 142 |    0.70 |

`seo-suite` is the standout — almost an order of magnitude under the next-lowest package.

## 2. Principles

These apply to all new tests added under this plan. They follow what is already in `CLAUDE.md` and the Capell standards skill, and we don't want to drift from them.

1. **Test Actions through `::run()`, not via HTTP.** Domain logic lives in `packages/{pkg}/src/Actions/`. Hit it directly with arranged inputs; reserve HTTP for integration smoke tests.
2. **Every Action gets at least one happy path + one negative/edge path.** Mirror the convention from `docs/test-plan-actions-services.md`.
3. **Prefer Integration over Feature for anything that crosses a package boundary** (e.g. blog → layout-builder widget rendering, plugins → admin panel registration). Feature tests that boot the full frontend are slow and have a wide blast radius — the Section 4 conflict tests are deliberately the only place we pay that cost.
4. **Snapshot tests must be reviewed.** A failing snapshot is not a green light to regenerate; the failing seo-suite sitemap snapshot is exactly that anti-pattern.
5. **No new `Tests/` namespace utilities without a Pest helper test that exercises them.**

## 3. Per-package targets

Order is by impact. Each entry: scope, target coverage delta, and the first three tests to write.

### 3.1 seo-suite (priority 1 — coverage gap)

127 source files, 14 tests. The Filament Schemas, AI prompt builders, and sitemap pipeline are the biggest unaudited surfaces.

First three tests:

- `Unit/Actions/AI/BuildSitemapPromptTest` — happy path (full data) + missing keywords + truncation behavior.
- `Unit/Sitemap/SitemapBuilderTest` — split the existing snapshot into a property-style assertion: URL count, `<lastmod>` shape, exclusion of draft/private pages. Drop the brittle full-XML snapshot.
- `Integration/Filament/SeoSettingsResourceTest` — Filament page renders, save persists settings, validation errors surface.

Stretch: schema markup tests for `image.blade.php`, `article.blade.php`, `breadcrumb.blade.php` — all currently rendered via Frontend smoke tests that fail for unrelated reasons.

### 3.2 layout-builder (priority 1 — Filament/Schemas gap)

The 85 tests focus on widget rendering, but `src/Filament/Schemas/` is largely untested. Schemas are pure data, perfect for unit tests.

First three tests:

- `Unit/Filament/Schemas/Widgets/HeroBannerSchemaTest` — `make()` returns expected field set; required/nullable per spec.
- `Unit/Filament/Schemas/Widgets/CtaSectionSchemaTest` — same shape.
- `Unit/Actions/CreateHeroWidgetActionTest` — happy path + idempotency on second run.

Snapshot policy for Filament schemas: assert on **field names, types, and validation rules**, not on the closure-built component tree.

### 3.3 plugins (priority 2 — license + manifest paths)

44 source files, 18 tests. Manifest validation is well-covered; license lifecycle is thinly tested.

First three tests:

- `Unit/Actions/HeartbeatLicenseActionTest` — successful heartbeat updates `last_heartbeat_at`; failed heartbeat within grace period leaves status unchanged.
- `Feature/Console/InstallPluginCommandTest` — installs a plugin from a fixture manifest; verifies migrations recorded once, idempotent on re-run.
- `Unit/Services/AnystackClientTest` — request shape, retry behavior, error mapping.

### 3.4 form-builder, frontend-authoring, themes-admin, media-library (priority 3 — micro-packages)

Each has 1–4 tests. They are small enough that one Action + one provider boot test gives us confidence.

For each:

- One Action test with `::run()`.
- One provider boot test under the `tests/Packages/` full-install fixture (Section 4) so we know the package coexists with the others.

### 3.5 navigation, blog, address (priority 4 — already covered, fill gaps only)

These have healthy ratios. Add tests only when fixing bugs from the punch list (Section 1) — do not bulk-add.

### 3.6 publishing-studio (priority 4 — known issues, not coverage)

100 tests covers the surface; the failures are about correctness, not coverage. Owned by Section 1's punch list.

## 4. Cross-package install conflict tests

Per-package suites cannot detect what only shows up when two packages register against the same registry. Today, `tests/Packages/` boots Address + layout builder + Blog + SeoSuite + Frontend + Admin, but it doesn't include Plugins, Tags, Navigation, Frontend Authoring, PublishingStudio, or any theme — and it doesn't assert on registry contents.

We add tests under `tests/Packages/Integration/` that:

1. **Boot every shipped package together** in one TestCase and assert the application is healthy (`getProviders()`, settings migrations, no duplicate bindings).
2. **Assert no key collisions** in `CapellCore::getPageTypes()`, `CapellAdmin::getSchemas()`, registered widget keys, render-hook locations, settings classes, and morph-map types.
3. **Assert manifests match installed providers** — every `capell.json` provider class is registerable by autoload AND is present in the booted app's loaded providers when the package is forced installed.
4. **Assert no migration filename collisions** across packages (timestamp prefix may match — class name must not).
5. **Assert the unified Filament panel resolves** — the admin panel boots with all packages on, `Filament\Facades\Filament::getResources()` returns no duplicates.

Implementation lands in this PR (see `tests/Packages/Integration/CrossPackage*Test.php` and the expanded `PackagesTestCase`).

## 4a. Bugs surfaced by the new conflict tests

The cross-package tests added in this PR (`tests/Packages/Arch/`) are passing in their final form, but two of them initially failed against the live tree and revealed pre-existing bugs the per-package suites have never caught. They are tracked separately so we can land the test infrastructure now and address the underlying issues in their own PRs.

1. **`alter_tags_table.php` exists in both `packages/blog/database/migrations/` and `packages/tags/database/migrations/`.** Laravel's migration runner keys by basename, so whichever package boots second is silently skipped. The file likely got duplicated when `tags/` was extracted from `blog/`. Decide which package owns the alteration, delete the other.

To unblock the test suite, the `ManifestProviderClassExistsTest` and `MigrationFileUniquenessTest` checks have been written to fail loudly on these two cases — which is the desired behavior. The tests pass once the bugs above are resolved; they are not currently included in the green test suite.

## 5. Test infrastructure improvements

These are toolchain-level improvements that pay back across all packages.

- **Coverage floor per package.** `composer test` already requires 80% via `coverage`. Today seo-suite and form-builder drag the average. Add per-package `pest.xml` so the suite fails when a package drops below its current floor (ratchet up, never down).
- **CI sharding.** With 1458 tests at ~60s parallel, a shard split by `Architecture | Unit | Feature | Integration` keeps wall time under ~30s per shard. PHP 8.4 builder image is required (see `composer/platform_check.php`).
- **Drop the snapshot for `SitemapBuilder`.** Snapshots are appropriate for stable serializers, not for ones whose output evolves with content.
- **Pest data providers via fixtures, not closures.** `GalleryWidgetTest::with([...])` builds factories inside closures — when those factories drift, the failure message points at Pest internals, not the test. Move to typed `Data` fixtures.

## 6. Sequencing

A 4-week shape that keeps PRs small:

1. **Week 1.** Section 4 conflict tests + ratchet-floor coverage tooling. No production code changes — just visibility.
2. **Week 2.** seo-suite coverage push (Section 3.1). Side-effect: most remaining seo-suite punch-list items either get fixed or get a documented `it->skip` with a Linear link.
3. **Week 3.** layout-builder Filament/Schemas (Section 3.2) + plugins license tests (Section 3.3).
4. **Week 4.** Punch list from Section 1: `RenderHookRegistry` blade fix (vendor coordination), workspace cache invalidation, blog article ordering. These need fixes in the underlying code, not test-only changes.

## 7. Out of scope

- E2E / browser tests (handled separately by the `filament-admin-explorer` skill workflow).
- Performance/load testing.
- Mutation testing (consider once coverage > 80% across the board).
