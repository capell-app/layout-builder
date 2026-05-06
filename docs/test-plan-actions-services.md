# Test Plan: Actions & Services Coverage

> **Historical note.** This document predates the move of AI functionality into `capell-app/seo-suite`. Entries in the _Admin Package → AI/\*_ section are legacy planning notes. New AI-assisted SEO tests belong under `packages/seo-suite/tests/`.

This document inventories Actions and Services across the Core, Admin, and SEO Suite-era packages and defines the test scope (Unit vs Integration) and representative scenarios per class. Tests live under each package's `tests/` directory.

Conventions:

- Pest tests only; Arrange–Act–Assert style.
- Actions invoked via `::run()` (never `handle()`).
- Use Storage::fake, File::spy, and container bindings/fakes.
- Minimum per class: one happy path + one edge/negative case.

---

## Admin Package

### Actions

- AddPageToNavigationAction — Unit; adds page to navigation; edge: missing page/navigation.
- AddRedirectUrlAction — Unit; builds redirect mapping; edge: invalid URL shape.
- AssignPermissionsToRole — Integration; assigns permissions; edge: missing role/permission.
- BuildDefaultTranslationsAction — Unit; generates translation stubs; edge: empty dataset.
- CheckSiteLanguagesMissingDomainsAction — Unit; detects missing domains; edge: complete domains.
- CheckTranslationCompletenessAction — Unit; computes completeness; edge: no translations.
- CreateDefaultPagesAction — Integration; creates default pages; edge: duplicates/idempotency.
- CreatePageAction — Integration; validated page creation; edge: invalid payload.
- CreatedModelAction — Integration; event/side effects; edge: unsupported model.
- DeletedModelAction — Integration; event/side effects; edge: unsupported model.
- UpdatedModelAction — Integration; event/side effects; edge: unsupported model.
- CreatedSiteAction — Integration; site creation; edge: missing dependency.
- DeletePageCacheAction — Integration; deletes cache entry; edge: file not found.
- DeletePagesCacheAction — Integration; deletes multiple cache entries; edge: none exist.
- DeleteUrlCacheAction — Integration; deletes by URL key; edge: key missing.
- EnsureSchemaImportsAction — Unit; ensures imports; edge: duplicate import.
- ExtractContentFromBlocksAction — Unit; extracts content; edge: invalid block.
- GenerateComponentKeyAction — Unit; deterministic key; edge: collision.
- GenerateSitemapAction — Integration; delegates to core generator; edge: no pages.
- GenerateStaticSiteAction — Integration; triggers static site generator; edge: FS error.
- GetAssetResourceUrlAction — Unit; resolves asset resource URL; edge: unknown asset.
- GetFlatComponentKeysAction — Unit; flattens component keys; edge: empty.
- GetMaxUploadSizeInBytes — Unit; returns configured size; edge: config missing.
- ModifyCreateAction — Unit; modifies Filament action config; edge: conflicts.
- MutateContentPresenterAction — Unit; mutates content payload; edge: invalid shape.
- MutateDefaultPageDataAction — Unit; merges defaults; edge: override precedence.
- NotifyClearCachedPagesAction — Integration; sends notification on clear; edge: no cached pages.
- PublishRecordAction — Integration; toggles publish state; edge: already published.
- ReplicateLayoutAction — Integration; replicate layout; edge: missing relations.
- ReplicateModelAction — Integration; replicate generic model; edge: constraints.
- ReplicatePageAction — Integration; replicate page; edge: nested set/draft handling.
- SavedPageAction — Integration; post-save side effects; edge: invalid state.
- UpdateUrlsAction — Integration; updates URLs; edge: conflicts.
- VisitUrlAction — Unit; builds visit URL; edge: invalid domain.
- AI/BaseAction — Unit; validates AiActionInput; edge: invalid context.
- AI/ApplyAiDraftAction — Integration; applies AI draft; edge: missing field mapping.
- AI/GeneratorPageContentAction — Integration; OpenAI provider mock; edge: provider failure.
- AI/RecordAiGenerationAction — Integration; persists history; edge: missing fields.
- AI/SuggestMetaDescriptionsAction — Integration; pipeline with provider; edge: rate limit.
- AI/SuggestPageTitlesAction — Integration; pipeline with provider; edge: provider error.

### Services

- SlugGenerator — Unit; slugify incl. accents; edge: collision handling.
- AI/AiFeatureRegistry — Unit; register/resolve features; edge: duplicate.
- AI/AiRateLimiter — Unit; window/token bucket; edge: burst over limit.
- AI/AiResponseParser — Unit; parse structured JSON; edge: malformed input.
- AI/AiTokenCounter — Unit; count tokens by model; edge: empty text.
- AI/OpenAIProvider — Integration; HTTP client mock; success/error.
- AI/PromptRepository — Unit; retrieve templates; edge: missing key.
- Loader/LanguageLoader — Integration; DB load; edge: not found.
- Loader/SiteLoader — Integration; DB load; edge: not found.
- LanguageUpdater — Integration; update language; edge: invalid data.
- Creator/DemoCreator — Integration; create demo dataset; edge: duplicates.
- Creator/LanguageCreator — Integration; create language; edge: invalid locale.
- Creator/LayoutCreator — Integration; create layout; edge: duplicate name.
- Creator/NavigationCreator — Integration; create navigation; edge: invalid structure.
- Creator/PageCreator — Integration; create page; edge: invalid type.
- Creator/SiteCreator — Integration; create site; edge: duplicate domain.
- Creator/ThemeCreator — Integration; create theme; edge: missing assets.

### Other Service-like (Admin)

- Cache/AIGenerationCache — Unit; key policy, TTL; edge: invalid inputs.
- Cache/RateLimitCache — Unit; TTL handling; edge: expiration.
- CapellAdminManager — Integration; orchestrations & container wiring smoke.
- Console Commands — Feature: exit codes, side effects via spies.
- Enums, Settings — Unit: helper methods (options/labels), serialization.

---

## Core Package

### Actions

- ColorConverterAction — Unit; hex/rgb/hsl conversions; edge: invalid.
- ColorTypeDetectorAction — Unit; detect type; edge: invalid input.
- UpdateRgbColorAction — Unit; update color fields; edge: clamp values.
- GetEditPageResourceUrlAction — Unit; build admin edit URL; edge: missing page.
- GetNameFromTranslationsAction — Unit; language fallback; edge: missing default.
- GetPluginsAction — Integration; list plugins; edge: none installed.
- GetResourceAssetsAction — Unit; resolve assets by resource; edge: unknown.
- GetResourceFromTypeAction — Unit; type-to-resource mapping; edge: invalid type.
- GenerateUniqueKeyAction — Unit; deterministic key; edge: collisions.
- IncrementNameAction — Unit; increment suffix logic; edge: overflow.
- InstallPackageAction — Integration; composer/filesystem mocks; error handling.
- RequirePackageAction — Integration; composer constraint; error handling.
- RemovePackageAction — Integration; composer removal; error handling.
- UninstallPackageAction — Integration; cleanup & removal; error handling.
- LoadSiteDomainFromUrlAction — Unit; parse domain; edge: malformed URL.
- PageDeletedAction — Integration; cache & relations side effects.
- RemovePageFromNavigationAction — Integration; remove page; edge: persistence.
- RenderContentAction — Unit; render blocks; edge: unsupported.
- UpdateUrlAction — Integration; compute & persist; edge: conflicts.
- UpdateTailwindClassesFile — Integration; modify safelist; edge: missing file.

### Services

- HtmlMinifier (`capell-app/html-optimizer`) — Unit; minify while preserving pre/code; edge cases.
- DatasetPublisher — Integration; writes files; File::spy; permission error.
- LanguageFlagsService — Unit; ISO code → asset path; edge: unsupported code.
- PageCacheService — Integration; exists, lastModified, path, delete, root, directories, allDirectories, files, allFiles, deleteDirectory; failure paths wrap exceptions with actionable messages.
- SignedUrlService — Unit; strong signature gen/verify; tampering fails.
- SitemapBuilder — Integration; build sitemap; edge: minimal when empty.
- SitemapGenerator — Integration; delegates and outputs; edge: no pages.
- StaticSiteExtensionRegistry — Unit; register/list; edge: duplicate.
- StaticSiteGenerator — Integration; writes static HTML/assets; edge: missing layout.

### Other Service-like (Core)

- Observers/\* — Integration: side effects (cache invalidation, relations).
- Console Commands — Feature: exit codes & side effects via spies.
- Casts/\* — Unit: casting behavior; invalid data.
- Enums helpers — Unit: labels/options consistency.

---

## Test Directory Structure

- tests/Admin/Unit/Actions/\*
- tests/Admin/Integration/Actions/\*
- tests/Admin/Unit/Services/\*
- tests/Admin/Integration/Services/\*
- tests/Admin/Feature/Commands/\*
- tests/Core/Unit/Actions/\*
- tests/Core/Integration/Actions/\*
- tests/Core/Unit/Services/\*
- tests/Core/Integration/Services/\*
- tests/Core/Feature/Commands/\*

## Shared Helpers & Setup

- tests/Pest.php: register shared datasets and helper functions.
- Use model factories for Site, Page, Layout, Language, Navigation.
- Use Storage::fake('page_cache') and File::spy() for filesystem interactions.
- Provide fakes/binds for OpenAI provider and RNG/Clock abstractions where needed.

## Phased Implementation (before running tests)

Phase 1: Core (PageCacheService, SignedUrlService, SitemapGenerator); HTML Optimizer (HtmlMinifier); Admin (SlugGenerator, DeletePageCacheAction, AI/AiRateLimiter, AI/SuggestPageTitlesAction).

Phase 2: Navigation/Pages/Types/Themes/Languages clusters in both packages.

Phase 3: Composer/Filesystem actions & Static site generators.

Phase 4: Console commands, observers, caches, AI integration.

Phase 5: Remaining actions/services + enums/settings helpers.

Each phase will add tests and validate via analysis, then run the suite once all are in place.
