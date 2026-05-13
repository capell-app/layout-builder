# Documentation Coverage Audit

Generated from the current `packages/*` code, package READMEs, package `docs/` files, and `capell.json` manifests. Treat this as an internal checklist before expanding public docs.

## Scope

- Package directories checked: 42
- Manifest-backed feature packages: 41
- Commands: 38
- Service providers: 58
- Registry/extension registration call sites: 113
- Config keys: 386
- Migrations: 97
- Actions: 330
- Public extension point files: 79

A surface is marked as code-only when its class name, command signature, migration filename, config key, or registry symbol does not appear in the package README or package docs. This is a string check, so review the recommended fixes before creating prose.

`block-library` is the extra package directory without a `capell.json` manifest. It is documented as a `Capell\ContentBlocks\` skeleton rather than listed as an optional installable feature.

## Optional Feature Map

Core documentation should describe these packages as optional unless a host application explicitly installs them.

`capell-app/layout-builder` is not an optional package in this repository anymore. The useful layout builder surface has moved into the admin/frontend core packages, so package docs should refer to "core layout builder APIs" rather than a separate package install.

| Feature             | Package                          | Manifest group / tier           | Surfaces                 | Documentation note                                                              |
| ------------------- | -------------------------------- | ------------------------------- | ------------------------ | ------------------------------------------------------------------------------- |
| Access Gate         | `capell-app/access-gate`         | Capell Operations / premium     | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| Address             | `capell-app/address`             | Capell Content / premium        | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| Agent Bridge        | `capell-app/agent-bridge`        | Capell Operations / premium     | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| AI Orchestrator     | `capell-app/ai-orchestrator`     | Capell Commercial / premium     | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| Blog                | `capell-app/blog`                | Capell Foundation / free        | admin, frontend, console | Optional package manifest present; avoid implying this feature is part of core. |
| Campaign Studio     | `capell-app/campaign-studio`     | Capell Growth / premium         | admin, frontend          | Optional package manifest present; avoid implying this feature is part of core. |
| Content Sections    | `capell-app/content-sections`    | Capell Foundation / free        | admin, frontend          | Optional package manifest present; avoid implying this feature is part of core. |
| Dashboard Reports   | `capell-app/dashboard-reports`   | Capell Operations / premium     | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| Demo Kit            | `capell-app/demo-kit`            | Capell Foundation / free        | admin, frontend          | Optional package manifest present; avoid implying this feature is part of core. |
| Deployments         | `capell-app/deployments`         | Capell Operations / premium     | admin, console           | Optional package manifest present; avoid implying this feature is part of core. |
| Diagnostics         | `capell-app/diagnostics`         | Capell Operations / premium     | admin, console           | Optional package manifest present; avoid implying this feature is part of core. |
| Email Studio        | `capell-app/email-studio`        | Capell Communications / premium | admin, frontend, console | Optional package manifest present; avoid implying this feature is part of core. |
| Events              | `capell-app/events`              | Capell Content / premium        | admin, frontend, console | Optional package manifest present; avoid implying this feature is part of core. |
| Form Builder        | `capell-app/form-builder`        | Capell FormBuilder / premium    | admin, frontend          | Optional package manifest present; avoid implying this feature is part of core. |
| Foundation Theme    | `capell-app/foundation-theme`    | Capell Foundation / free        | admin, frontend          | Optional package manifest present; avoid implying this feature is part of core. |
| Frontend Authoring  | `capell-app/frontend-authoring`  | Capell Foundation / free        | frontend, console        | Optional package manifest present; avoid implying this feature is part of core. |
| Frontend Optimizer  | `capell-app/frontend-optimizer`  | Capell Foundation / premium     | frontend                 | Optional package manifest present; avoid implying this feature is part of core. |
| GA4 Reports         | `capell-app/ga4-reports`         | Capell Growth / premium         | admin, console           | Optional package manifest present; avoid implying this feature is part of core. |
| Hero                | `capell-app/hero`                | Capell Foundation / free        | frontend, console        | Optional package manifest present; avoid implying this feature is part of core. |
| HTML Cache          | `capell-app/html-cache`          | Capell Foundation / free        | admin, frontend          | Optional package manifest present; avoid implying this feature is part of core. |
| Insights            | `capell-app/insights`            | Capell Growth / premium         | admin, frontend          | Optional package manifest present; avoid implying this feature is part of core. |
| Login Audit         | `capell-app/login-audit`         | Capell Operations / premium     | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| Media AI            | `capell-app/media-ai`            | Capell Media / premium          | admin, console           | Optional package manifest present; avoid implying this feature is part of core. |
| Media Library       | `capell-app/media-library`       | Capell Foundation / free        | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| Migration Assistant | `capell-app/migration-assistant` | Capell Operations / premium     | admin, console           | Optional package manifest present; avoid implying this feature is part of core. |
| Navigation          | `capell-app/navigation`          | Capell Foundation / free        | admin, frontend, console | Optional package manifest present; avoid implying this feature is part of core. |
| Newsletter          | `capell-app/newsletter`          | Capell Marketing / premium      | admin, frontend          | Optional package manifest present; avoid implying this feature is part of core. |
| Notes               | `capell-app/notes`               | Capell Collaboration / premium  | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| Password Policy     | `capell-app/password-policy`     | Capell Operations / premium     | admin, console           | Optional package manifest present; avoid implying this feature is part of core. |
| Public Actions      | `capell-app/public-actions`      | Capell Automation / premium     | admin, frontend, console | Optional package manifest present; avoid implying this feature is part of core. |
| Publishing Studio   | `capell-app/publishing-studio`   | Capell Publishing Pro / premium | admin, console           | Optional package manifest present; avoid implying this feature is part of core. |
| Search              | `capell-app/search`              | Capell Search & SEO / premium   | admin, frontend, console | Optional package manifest present; avoid implying this feature is part of core. |
| SEO Suite           | `capell-app/seo-suite`           | Capell Search & SEO / premium   | admin, frontend, console | Optional package manifest present; avoid implying this feature is part of core. |
| Site Discovery      | `capell-app/site-discovery`      | Capell Search & SEO / premium   | admin, frontend, console | Optional package manifest present; avoid implying this feature is part of core. |
| Tags                | `capell-app/tags`                | Capell Foundation / free        | admin, console           | Optional package manifest present; avoid implying this feature is part of core. |
| Theme Agency        | `capell-app/theme-agency`        | Capell Themes / premium         | frontend                 | Optional package manifest present; avoid implying this feature is part of core. |
| Theme Corporate     | `capell-app/theme-corporate`     | Capell Themes / premium         | frontend                 | Optional package manifest present; avoid implying this feature is part of core. |
| Theme Saas          | `capell-app/theme-saas`          | Capell Themes / premium         | frontend                 | Optional package manifest present; avoid implying this feature is part of core. |
| Translation Manager | `capell-app/translation-manager` | Capell Admin / premium          | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| Welcome Tour        | `capell-app/welcome-tour`        | Capell Foundation / free        | admin                    | Optional package manifest present; avoid implying this feature is part of core. |
| Wordpress Importer  | `capell-app/wordpress-importer`  | Capell Operations / premium     | admin, console           | Optional package manifest present; avoid implying this feature is part of core. |

## Reader Examples To Add Or Keep

Keep these examples near the relevant package docs as they move out of this audit.

### Register a page schema extender

From Campaign Studio's service provider pattern:

```php
use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\CampaignStudio\Filament\Extenders\Page\CampaignPageSchemaExtender;

$this->app->singleton(CampaignPageSchemaExtender::class);
$this->app->tag(CampaignPageSchemaExtender::class, PageSchemaExtender::TAG);
```

### Register package settings

From Newsletter's settings registration pattern:

```php
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Newsletter\Settings\NewsletterSettings;

$registry = resolve(SettingsSchemaRegistry::class);
$registry->registerSettingsClass('newsletter', NewsletterSettings::class);
```

### Register package migrations

From packages using Spatie package tools:

```php
$package
    ->name('capell-campaign-studio')
    ->hasConfigFile('capell-campaign-studio')
    ->hasMigrations([
        '2026_05_10_190843_01_create_campaign_groups_table',
        '2026_05_10_190843_03_create_campaign_landing_pages_table',
    ]);
```

### Clear cached URLs for a model

From HTML Cache's action API:

```php
use Capell\HtmlCache\Actions\ClearCachedUrlsForModelAction;

$clearedUrlCount = ClearCachedUrlsForModelAction::run($model, refresh: true);
```

### Add a frontend render hook

From Search's header hook pattern:

```php
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;

$registry->register(
    RenderHookLocation::HeaderAfter,
    static fn (): string => view('capell-search::components.form')->render(),
);
```

### Declare package manifest metadata

Use `capell.json` to keep package availability explicit:

```json
{
    "name": "capell-app/hero",
    "slug": "hero",
    "kind": "package",
    "surfaces": ["frontend", "console"],
    "commands": {
        "setup": "capell:hero-setup"
    }
}
```

## Package-Specific Follow-Up

Use this list before expanding more prose. It keeps the next docs pass focused on surfaces developers actually touch.

| Package               | Next documentation fix                                                                                                                                                                                                                   | Public config to document                                                                                                                                                                                                                | Internal config to leave out unless needed                                                                    |
| --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| `seo-suite`           | Keep `docs/extending-seo-suite.md` current with `SchemaTemplateRegistry`, `SectionRegistry`, content targets, AI settings, render hooks, publish reports, and admin extenders. Add examples beside any new AI or schema extension point. | `features`, `prism`, `prompts`, `rate_limiting`, `cache.ttl`, `ai_discovery`, `search_console`                                                                                                                                           | crawler seed arrays, provider default lists, prompt internals that are package-maintained                     |
| `insights`            | Keep `docs/tracking-and-consent.md` focused on beacon routes, consent writes, retention, hashing, and ignored paths. Add examples for any new server-side event action.                                                                  | `enabled`, `route_prefix`, `track_page_views`, `track_clicks`, `automatic_click_tracking`, `require_consent_for_all_regions`, `policy_version`, `retention_days`, `hash_visitor_data`, `hash_salt`, `ignored_paths`, `ignored_selectors` | table names unless a host app is overriding storage                                                           |
| `publishing-studio`   | Keep `docs/extending-publishing-studio.md` as the home for `WorkspaceRegistry`, release item contributors, workspace table actions, page extenders, and workspace query macros.                                                          | settings exposed through `PublishingStudioSettings` and install commands                                                                                                                                                                 | internal workspace scheduler metadata and migration bridge wiring unless a public extension point consumes it |
| `frontend-optimizer`  | Keep `docs/assets-and-render-profiles.md` as the home for layout assets, widget assets, render profiles, critical CSS generation, and the Blade directive.                                                                               | `enabled`, `scope`, `paths.manifests`, `paths.critical_css`, `playwright.*`, `CAPELL_FRONTEND_OPTIMIZER_NODE`                                                                                                                            | render profile signatures and optimization run internals                                                      |
| `events`              | Keep `docs/event-extension-points.md` as the home for RSVP actions, booking providers, registration providers, feeds, SEO schema, and Publishing Studio integration.                                                                     | No package config currently shipped.                                                                                                                                                                                                     | Livewire version detection and About command version reporting                                                |
| `email-studio`        | Keep `docs/templates-and-providers.md` as the home for template registration, provider adapters, `SendEmailAction`, queueing, tracking, and webhook normalization.                                                                       | `default_provider`, `queue`, `track_opens`, `track_clicks`, `body_retention_days`, `webhook_tolerance_seconds`, `public_route_prefix`, `tracking_token_ttl_days`, rate limiter names                                                     | table names unless install docs need them                                                                     |
| `migration-assistant` | Keep `docs/extension-points.md` as the home for source readers, targets, relation resolvers, row contributors, context resolvers, collision detection, and ownership mapping.                                                            | `enabled`, `disk`, notification channels, queue/connection options                                                                                                                                                                       | importer state machine internals and rollback report storage details                                          |
| `access-gate`         | Keep `docs/access-requests.md` focused on request methods, fields, Public Actions integration, gated routes, and cache invalidation. Add a config table only for host-app knobs.                                                         | route prefix, identity methods, approval policy, token TTL, grant duration, status endpoint, page cache aliases                                                                                                                          | cookie internals and download resolver indexes unless a host app changes them                                 |
| `agent-bridge`        | Keep `docs/capabilities.md` as the home for capability providers, capability actions, risk/confirmation rules, token scopes, and bridge config.                                                                                          | routes, confirmation TTL, public docs paths, user resource bridge toggle, knowledge settings                                                                                                                                             | token, confirmation, and audit table names unless storage is being customized                                 |
| `diagnostics`         | Keep `docs/command-palette.md` as the home for command palette providers, command metadata, abilities, risk levels, and confirmation behavior.                                                                                           | No public config currently shipped.                                                                                                                                                                                                      | dashboard widget registration and health action internals                                                     |
| `ga4-reports`         | Keep `docs/data-client.md` as the home for GA4 config, the data client contract, sync windows, and null-client behavior.                                                                                                                 | `enabled`, `property_id`, `credentials_path`, `sync_days`, `route_slug`                                                                                                                                                                  | table names unless install/storage docs need them                                                             |
| `login-audit`         | Keep `docs/settings-and-ip-resolution.md` as the home for settings, CDN IP resolution, middleware alias, observer behavior, and notification toggles.                                                                                    | table name, connection, event/listener mapping, notification toggles, purge window, `behind_cdn`                                                                                                                                         | vendor authentication-log internals                                                                           |
| `newsletter`          | Keep `docs/subscription-workflow.md` focused on form mappings, providers, segments, tags, exports/imports, and retry command behavior.                                                                                                   | default confirmation mode, provider, form mappings, import/export paths, consent events                                                                                                                                                  | table names and dashboard query options                                                                       |
| `navigation`          | Keep `docs/rendering-and-sync.md` as the home for render models, names resolver, page syncer, header hook, and cache invalidation.                                                                                                       | No package config currently shipped.                                                                                                                                                                                                     | internal item loader cache details unless debugging render output                                             |
| `public-actions`      | Keep `docs/actions-and-integrations.md` focused on handlers, destination adapters, integration tokens, Zapier routes, throttling, and provider presets.                                                                                  | route prefixes, API rate limits, action/destination/provider definitions, token TTL                                                                                                                                                      | protected table names and internal query defaults                                                             |
| `search`              | Keep `docs/drivers-and-logging.md` as the home for search driver binding, public route config, result logging, click logging, and retention.                                                                                             | enabled flag, header hook toggle, driver, route path, pagination, excerpt, min query length, logging, hashing, database/scout mappings, retention                                                                                        | dashboard query defaults unless changing admin reports                                                        |
| `translation-manager` | Keep `docs/sources-stores-and-ai.md` as the home for source resolvers, file stores, AI translators, locale validation, and package source write rules.                                                                                   | source locale, locale pattern, app source, package paths, vendor namespaces, package source write toggle                                                                                                                                 | per-file comparison internals                                                                                 |
| `welcome-tour`        | Keep `docs/steps-and-settings.md` as the home for step config, programmatic step registration, dashboard replacement, settings, and selector guidance.                                                                                   | enabled flag and step fields                                                                                                                                                                                                             | Filament tour plugin wiring unless debugging admin boot                                                       |

## Reader Examples Now Covered

The current package docs now include copy-paste examples for:

- registering page schema extenders and settings;
- registering package migrations;
- clearing HTML cache for a model;
- adding frontend render hooks;
- registering frontend authoring editable regions;
- registering Public Actions handlers and adapters;
- writing Newsletter provider adapters and segment providers;
- adding Access Gate request methods and registration fields;
- registering SEO schema templates, AI Creator sections, and AI content targets;
- registering Frontend Optimizer layout and widget assets;
- writing Event booking and registration providers;
- registering Email Studio templates and provider adapters;
- registering Migration Assistant source readers, targets, and relation resolvers;
- registering Publishing Studio draftable models, release workspace contributors, and workspace table actions.
- registering Agent Bridge capabilities and capability actions;
- registering Diagnostics command palette providers;
- swapping GA4 data clients;
- configuring Login Audit CDN IP resolution;
- rendering and syncing Navigation records;
- binding Search drivers and handling search logs;
- replacing Translation Manager sources, file stores, and AI translators;
- adding Welcome Tour steps from config or code.

## Coverage Matrix

| Package               | Current doc location                                                                                                                                                                                                                                                                                                  | Code surfaces                                                                                               | Code-only gaps                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | Recommended fix                                                                                                                                                                  |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `access-gate`         | README.md, docs/access-requests.md                                                                                                                                                                                                                                                                                    | commands 3; providers 1; registries 2; config 35; migrations 9; actions 23; extension points 2              | registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerProtectedTable`)<br>config: 26/35 (`access-gate.access_gate.area`, `access-gate.access_gate.browser_token`, `access-gate.aliases`, `access-gate.approval_limit`, `access-gate.approval_strategy`, and 21 more)<br>actions: 20/23 (`ApproveNextRegistrationsAction`, `ApproveRegistrationAction`, `AreaIsCurrentlyGatingAction`, `ConsumeAccessGateClaimTokenAction`, `CreateAccessGateBrowserTokenAction`, and 15 more)                                                                                                                                                                                     | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `address`             | README.md, docs/address-api.md, docs/address-database.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                      | commands 3; providers 1; registries 2; config 0; migrations 2; actions 0; extension points 0                | registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerVendorAsset`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `agent-bridge`        | README.md, docs/boost-integration.md, docs/capabilities.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                    | commands 0; providers 1; registries 2; config 9; migrations 3; actions 9; extension points 5                | registries: 2/2 (`CapellAdmin::registerAdminBridge`, `registerSettingsClass`)<br>config: 9/9 (`capell-agent-bridge.confirmation_ttl_minutes`, `capell-agent-bridge.enable_user_resource_bridge`, `capell-agent-bridge.home`, `capell-agent-bridge.knowledge`, `capell-agent-bridge.public_docs_paths`, and 4 more)<br>actions: 9/9 (`AuditAgentBridgeCapabilityAction`, `ClearCapellCacheCapabilityAction`, `ConfirmAgentBridgeCapabilityAction`, `CreateAgentBridgeTokenAction`, `CreateDraftPageCapabilityAction`, and 4 more)<br>extension: 1/5 (`AgentBridgeUserSchemaExtender`)                                                                                            | Keep capability examples, scope rules, risk, confirmation, and bridge config in `docs/capabilities.md`; keep token table internals out of public setup docs.                     |
| `ai-orchestrator`     | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 0; providers 1; registries 0; config 0; migrations 0; actions 3; extension points 2                | No code-only surface detected by string check.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | Keep current docs synchronized when adding new actions, commands, or registry calls.                                                                                             |
| `block-library`       | README.md                                                                                                                                                                                                                                                                                                             | commands 0; providers 1 empty skeleton; registries 0; config 0; migrations 0; actions 0; extension points 0 | No runtime surface beyond the reserved `Capell\ContentBlocks\` namespace.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | Keep as a placeholder doc until the first real block primitive lands; document the first public extension point in the same change that adds it.                                 |
| `blog`                | README.md, docs/blog-api.md, docs/blog-database.md, docs/credits-and-acknowledgements.md, docs/media-attachment.md, docs/overview.md                                                                                                                                                                                  | commands 6; providers 4; registries 7; config 0; migrations 1; actions 7; extension points 0                | registries: 7/7 (`CapellCore::registerComponents`, `CapellCore::registerModelRelations`, `CapellCore::registerModels`, `CapellCore::registerPageType`, `CapellCore::registerPageVariation`, and 2 more)<br>actions: 7/7 (`CreateBlogHeroDemoContentAction`, `CreateBlogPagesAction`, `EnsureArticlePublishingDefaultsAction`, `EnsureBlogPublishingSurfaceAction`, `GenerateArchiveUrl`, and 2 more)                                                                                                                                                                                                                                                                            | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `campaign-studio`     | README.md, docs/campaign-studio-api.md, docs/campaign-studio-database.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                      | commands 1; providers 3; registries 8; config 10; migrations 5; actions 15; extension points 0              | registries: 8/8 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerNavigationGroup`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerComponents`, `CapellCore::registerModels`, and 3 more)<br>config: 10/10 (`capell-campaign-studio.conversion_cookie`, `capell-campaign-studio.conversion_goals`, `capell-campaign-studio.conversions`, `capell-campaign-studio.cta_blocks`, `capell-campaign-studio.enabled`, and 5 more)<br>actions: 15/15 (`ApplyCampaignPageDefaultsAction`, `BuildCampaignConversionFunnelAction`, `BuildCampaignOverviewStatsAction`, `BuildCampaignUrlAction`, `BuildConversionAttributionAction`, and 10 more)                 | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `content-sections`    | README.md                                                                                                                                                                                                                                                                                                             | commands 0; providers 1; registries 4; config 5; migrations 1; actions 11; extension points 1               | registries: 4/4 (`CapellAdmin::registerAsset`, `CapellCore::registerAsset`, `CapellCore::registerModels`, `CapellCore::registerPageType`)<br>config: 5/5 (`capell-content-sections.assets`, `capell-content-sections.color`, `capell-content-sections.icon`, `capell-content-sections.model`, `capell-content-sections.section`)<br>actions: 11/11 (`BuildSectionDemoDataAction`, `CreateContentAction`, `CreateHeroContentTypeAction`, `EnsureSectionTypeForKeyAction`, `ModifyContentSelectCreateAction`, and 6 more)                                                                                                                                                         | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `dashboard-reports`   | README.md, docs/credits-and-acknowledgements.md                                                                                                                                                                                                                                                                       | commands 0; providers 2; registries 1; config 0; migrations 0; actions 2; extension points 0                | registries: 1/1 (`CapellAdmin::registerDashboardWidget`)<br>actions: 2/2 (`BuildDefaultContentHealthAction`, `BuildPublishingTrendAction`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `demo-kit`            | README.md, docs/credits-and-acknowledgements.md                                                                                                                                                                                                                                                                       | commands 3; providers 1; registries 1; config 23; migrations 0; actions 1; extension points 0               | registries: 1/1 (`CapellAdmin::registerExtensionPage`)<br>config: 23/23 (`capell-demo-kit.archive`, `capell-demo-kit.checksum`, `capell-demo-kit.children`, `capell-demo-kit.code`, `capell-demo-kit.color`, and 18 more)<br>actions: 1/1 (`InsertExampleSiteDataAction`)                                                                                                                                                                                                                                                                                                                                                                                                       | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `deployments`         | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 0; providers 1; registries 1; config 7; migrations 1; actions 5; extension points 2                | registries: 1/1 (`CapellAdmin::registerExtensionPage`)<br>config: 7/7 (`capell-deployments.bitbucket`, `capell-deployments.client_id`, `capell-deployments.client_secret`, `capell-deployments.enabled`, `capell-deployments.github`, and 2 more)<br>actions: 5/5 (`ConnectDeploymentAction`, `CreateOAuthStateAction`, `PrepareComposerRequirementCommitAction`, `PublishComposerRequirementAction`, `ValidateOAuthStateAction`)                                                                                                                                                                                                                                               | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `diagnostics`         | README.md, docs/command-palette.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                            | commands 0; providers 2; registries 3; config 0; migrations 1; actions 15; extension points 1               | registries: 3/3 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `PageSchemaExtender::TAG`)<br>actions: 15/15 (`BuildCacheHealthAction`, `BuildConfigDriftAction`, `BuildContentGraphHealthAction`, `BuildMigrationsHealthAction`, `BuildPackagesInstalledAction`, and 10 more)                                                                                                                                                                                                                                                                                                                                                                   | Keep command palette provider examples in `docs/command-palette.md`; keep dashboard health action internals in code/tests.                                                       |
| `email-studio`        | README.md, docs/email-studio-api.md, docs/email-studio-database.md, docs/overview.md, docs/templates-and-providers.md                                                                                                                                                                                                 | commands 0; providers 3; registries 2; config 21; migrations 10; actions 7; extension points 1              | registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerProtectedTable`)<br>config: 21/21 (`capell-email-studio.body_retention_days`, `capell-email-studio.default_provider`, `capell-email-studio.events`, `capell-email-studio.messages`, `capell-email-studio.profiles`, and 16 more)                                                                                                                                                                                                                                                                                                                                                                            | Keep provider, template, and send examples in `docs/templates-and-providers.md`; add migration/table detail only if host apps need custom table names.                           |
| `events`              | README.md, docs/event-extension-points.md                                                                                                                                                                                                                                                                             | commands 1; providers 1; registries 7; config 0; migrations 5; actions 15; extension points 3               | registries: 7/7 (`CapellAdmin::registerExtensionPage`, `CapellCore::registerModels`, `CapellCore::registerPageType`, `CapellCore::registerPageVariation`, `CapellCore::registerVendorAsset`, and 2 more)<br>actions: 15/15 (`BuildCalendarFeedAction`, `BuildEventSchemaAction`, `CancelOccurrenceAction`, `EnsureEventPublishingDefaultsAction`, `EnsureEventPublishingSurfaceAction`, and 10 more)<br>extension: 1/3 (`RegisterEventSchemaHooks`)                                                                                                                                                                                                                             | Keep RSVP, booking provider, registration provider, feed, schema, and Publishing Studio notes in `docs/event-extension-points.md`.                                               |
| `form-builder`        | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 0; providers 1; registries 2; config 3; migrations 2; actions 5; extension points 0                | registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerVendorAsset`)<br>config: 3/3 (`capell-form-builder.collect_ip_address`, `capell-form-builder.collect_user_agent`, `capell-form-builder.store_submissions`)<br>actions: 5/5 (`ArchiveSubmissionAction`, `BuildFormValidationRulesAction`, `CreateSubmissionAction`, `MarkSubmissionReadAction`, `MarkSubmissionSpamAction`)                                                                                                                                                                                                                                                                                  | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `foundation-theme`    | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 2; providers 2; registries 3; config 20; migrations 0; actions 1; extension points 2               | registries: 3/3 (`CapellCore::registerModelInterceptor`, `CapellCore::registerVendorAsset`, `registerSettingsClass`)<br>config: 20/20 (`capell-foundation-theme.asset_build_tool`, `capell-foundation-theme.autoprefixer`, `capell-foundation-theme.fontaine`, `capell-foundation-theme.imports`, `capell-foundation-theme.laravel-vite-plugin`, and 15 more)<br>actions: 1/1 (`InstallFoundationThemeLayoutDefaultsAction`)                                                                                                                                                                                                                                                    | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `frontend-authoring`  | README.md, docs/credits-and-acknowledgements.md, docs/editable-regions.md, docs/in-page-editing.md, docs/overview.md                                                                                                                                                                                                  | commands 0; providers 1; registries 0; config 4; migrations 0; actions 4; extension points 0                | config: 2/4 (`capell-frontend-authoring.page_content`, `capell-frontend-authoring.page_title`)<br>actions: 2/4 (`BuildEditableRegionManifestAction`, `CollectAffectedCachedUrlsAction`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `frontend-optimizer`  | README.md, docs/assets-and-render-profiles.md                                                                                                                                                                                                                                                                         | commands 0; providers 1; registries 0; config 12; migrations 1; actions 7; extension points 1               | config: 12/12 (`capell-frontend-optimizer.critical_css`, `capell-frontend-optimizer.enabled`, `capell-frontend-optimizer.height`, `capell-frontend-optimizer.manifests`, `capell-frontend-optimizer.node_binary`, and 7 more)<br>actions: 7/7 (`GenerateCriticalCssAction`, `PersistRenderProfileAction`, `PrepareRenderProfileAction`, `RenderProfileAssetsAction`, `ResolveOptimizationScopeAction`, and 2 more)                                                                                                                                                                                                                                                              | Keep registry, profile, and critical CSS examples in `docs/assets-and-render-profiles.md`; leave signature internals out of public prose.                                        |
| `ga4-reports`         | README.md, docs/credits-and-acknowledgements.md, docs/data-client.md                                                                                                                                                                                                                                                  | commands 1; providers 2; registries 6; config 9; migrations 3; actions 8; extension points 3                | registries: 6/6 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, and 1 more)<br>config: 9/9 (`capell-ga4-reports.credentials_path`, `capell-ga4-reports.daily_metrics`, `capell-ga4-reports.enabled`, `capell-ga4-reports.page_metrics`, `capell-ga4-reports.property_id`, and 4 more)<br>actions: 8/8 (`BuildGA4ReportsOverviewAction`, `BuildGA4ReportsTrendAction`, `BuildGA4ReportsWindowAction`, `BuildTopGA4ReportsPagesAction`, `PersistGA4ReportsDailyMetricAction`, and 3 more)                                                 | Keep GA4 config, data client, and null-client behavior in `docs/data-client.md`; document table names only when storage is customized.                                           |
| `hero`                | README.md                                                                                                                                                                                                                                                                                                             | commands 1; providers 1; registries 1; config 0; migrations 0; actions 1; extension points 0                | registries: 1/1 (`CapellCore::registerVendorAsset`)<br>actions: 1/1 (`InstallHeroLayoutDefaultsAction`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `html-cache`          | README.md, docs/cache-invalidation.md                                                                                                                                                                                                                                                                                 | commands 1; providers 1; registries 1; config 13; migrations 1; actions 14; extension points 1              | registries: 1/1 (`CapellAdmin::registerAdminBridge`)<br>config: 5/13 (`capell-html-cache.cache_vary_headers`, `capell-html-cache.internal_requests`, `capell-html-cache.site_health_cached_url_limit`, `capell-html-cache.site_health_public_html_scan_limit`, `capell-html-cache.site_health_unindexed_public_html_scan_limit`)<br>actions: 8/14 (`BuildCacheMapOverviewAction`, `BuildCachedModelUrlDiagnosticsAction`, `BuildHtmlCachePublicOutputSafetyDiagnosticsAction`, `ClearAllHtmlCacheAction`, `DeletePageCacheAction`, and 3 more)                                                                                                                                  | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `insights`            | README.md, docs/credits-and-acknowledgements.md, docs/overview.md, docs/tracking-and-consent.md                                                                                                                                                                                                                       | commands 1; providers 2; registries 8; config 18; migrations 6; actions 17; extension points 3              | registries: 8/8 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, and 3 more)<br>config: 18/18 (`capell-insights.automatic_click_tracking`, `capell-insights.consents`, `capell-insights.default_consent_region`, `capell-insights.enabled`, `capell-insights.events`, and 13 more)<br>actions: 17/17 (`BuildInsightsOverviewStatsAction`, `BuildJourneyTimelineAction`, `BuildLiveInsightsStatsAction`, `BuildPopularPagesQueryAction`, `BuildRecentJourneysQueryAction`, and 12 more)<br>extension: 1/3 (`RegisterInsightsTrackerHook`) | Keep beacon, consent, retention, hashing, and ignored-path notes in `docs/tracking-and-consent.md`; avoid documenting raw table internals unless storage is being customized.    |
| `login-audit`         | README.md, docs/credits-and-acknowledgements.md, docs/overview.md, docs/settings-and-ip-resolution.md                                                                                                                                                                                                                 | commands 0; providers 2; registries 5; config 17; migrations 1; actions 4; extension points 2               | registries: 5/5 (`CapellAdmin::registerAdminBridge`, `CapellAdmin::registerDashboardWidget`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `registerSettingsClass`)<br>config: 17/17 (`login-audit.behind_cdn`, `login-audit.db_connection`, `login-audit.enabled`, `login-audit.events`, `login-audit.failed`, and 12 more)<br>actions: 4/4 (`ApplyLoginAuditSettingsAction`, `BuildLoginAuditsQueryAction`, `ResolveLoginAuditIpAddressAction`, `ShouldTrackUserIpAddressesAction`)<br>extension: 1/2 (`LoginAuditUserSchemaExtender`)                                                                                                                  | Keep settings, middleware, observer, notifications, retention, and CDN IP guidance in `docs/settings-and-ip-resolution.md`.                                                      |
| `media-ai`            | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 0; providers 1; registries 0; config 1; migrations 0; actions 0; extension points 1                | config: 1/1 (`capell-media-ai.enabled`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `media-library`       | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 1; providers 0; registries 2; config 0; migrations 0; actions 2; extension points 0                | registries: 2/2 (`CapellAdmin::registerExtensionPage`, `CapellCore::registerModels`)<br>actions: 1/2 (`BuildMediaHealthQueryAction`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `migration-assistant` | README.md, docs/credits-and-acknowledgements.md, docs/extension-points.md, docs/import-export-workflow.md, docs/migration-assistant.md, docs/overview.md                                                                                                                                                              | commands 0; providers 1; registries 1; config 19; migrations 2; actions 7; extension points 8               | registries: 1/1 (`CapellCore::registerModels`)<br>config: 19/19 (`migration-assistant.channels`, `migration-assistant.completed`, `migration-assistant.connection`, `migration-assistant.disk`, `migration-assistant.enabled`, and 14 more)<br>actions: 7/7 (`BuildImportValidationSummaryAction`, `BuildPageReviewRows`, `BuildRelationResolveRowsAction`, `CancelImportSessionAction`, `CreateImportRollbackReportAction`, and 2 more)                                                                                                                                                                                                                                        | Keep source reader, target, relation resolver, context, collision, and ownership examples in `docs/extension-points.md`.                                                         |
| `navigation`          | README.md, docs/credits-and-acknowledgements.md, docs/overview.md, docs/rendering-and-sync.md                                                                                                                                                                                                                         | commands 2; providers 1; registries 4; config 0; migrations 1; actions 5; extension points 3                | registries: 4/4 (`CapellCore::registerModels`, `CapellCore::registerPageType`, `RenderHookRegistry`, `RenderHookRegistry`)<br>actions: 5/5 (`AddPageToNavigationAction`, `BuildNavigationRenderModelAction`, `RemovePageFromNavigationAction`, `ReplicateSiteNavigationsAction`, `ResolveNavigationItemModelsAction`)<br>extension: 1/3 (`RegisterFoundationHeaderNavigationHook`)                                                                                                                                                                                                                                                                                              | Keep render model, names resolver, page syncer, header hook, and cache notes in `docs/rendering-and-sync.md`.                                                                    |
| `newsletter`          | README.md, docs/subscription-workflow.md                                                                                                                                                                                                                                                                              | commands 1; providers 2; registries 5; config 22; migrations 11; actions 16; extension points 4             | registries: 5/5 (`CapellAdmin::registerNavigationGroup`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `registerSettingsClass`)<br>config: 17/22 (`capell-newsletter.consent_events`, `capell-newsletter.default_confirmation_mode`, `capell-newsletter.enabled_by_default`, `capell-newsletter.form_mappings`, `capell-newsletter.import_batches`, and 12 more)<br>actions: 9/16 (`CreateUnsubscribeTokenAction`, `EvaluateNewsletterSegmentAction`, `ExportSubscribersAction`, `HandleProviderWebhookAction`, `ImportSubscribersAction`, and 4 more)                                                               | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `notes`               | README.md                                                                                                                                                                                                                                                                                                             | commands 0; providers 2; registries 4; config 0; migrations 1; actions 7; extension points 1                | registries: 4/4 (`CapellAdmin::registerExtensionPage`, `CapellAdmin::registerUserMenuItem`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`)<br>actions: 7/7 (`AssignNoteUsersAction`, `BuildUserAttentionCountsAction`, `CompleteNoteAssignmentAction`, `CreateNoteAction`, `MentionNoteUsersAction`, and 2 more)<br>extension: 1/1 (`CapellNotes`)                                                                                                                                                                                                                                                                                                         | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `password-policy`     | README.md, docs/credits-and-acknowledgements.md                                                                                                                                                                                                                                                                       | commands 0; providers 1; registries 3; config 1; migrations 2; actions 5; extension points 1                | registries: 3/3 (`CapellAdmin::registerAdminBridge`, `CapellAdmin::registerExtensionPage`, `registerSettingsClass`)<br>config: 1/1 (`capell-password-policy.enabled`)<br>actions: 5/5 (`EvaluatePasswordPolicyAction`, `MarkUserForPasswordChangeAction`, `RecordPasswordHistoryAction`, `UpdatePasswordAction`, `ValidatePasswordChangeAction`)                                                                                                                                                                                                                                                                                                                                | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `public-actions`      | README.md, docs/actions-and-integrations.md, docs/provider-presets.md                                                                                                                                                                                                                                                 | commands 0; providers 1; registries 2; config 26; migrations 5; actions 9; extension points 2               | registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerProtectedTable`)<br>config: 18/26 (`capell-public-actions.actions`, `capell-public-actions.adapter`, `capell-public-actions.adapters`, `capell-public-actions.api_rate_limit`, `capell-public-actions.destinations`, and 13 more)<br>actions: 7/9 (`BuildPublicActionIntegrationQueryAction`, `BuildZapierSubmissionPayloadAction`, `ListPublicActionOptionsAction`, `ResolvePublicActionForIntegrationTokenAction`, `ResolvePublicActionIntegrationTokenAction`, and 2 more)                                                                                                                               | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `publishing-studio`   | README.md, docs/credits-and-acknowledgements.md, docs/extending-publishing-studio.md, docs/overview.md, docs/page-creation-and-approval-flow.md, docs/page-drafts-and-publishing.md, docs/publishing-studio-draftable-contract.md, docs/publishing-studio.md, docs/publishing-workflow.md, docs/release-workspaces.md | commands 3; providers 3; registries 8; config 0; migrations 10; actions 32; extension points 9              | registries: 8/8 (`CapellAdmin::registerAdminBridge`, `CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, and 3 more)<br>actions: 31/32 (`AdvancePageImportToValidationAction`, `BuildActivityTrailQueryAction`, `BuildContentHealthAction`, `BuildContentSchedulerEventsAction`, `BuildMyWorkQueueAction`, and 26 more)<br>extension: 6/9 (`CapellPublishingStudio`, `PublishingStudioPageEditExtender`, `PublishingStudioPageExportExtender`, `PublishingStudioPageResourcePageExtender`, `PublishingStudioPageTableExtender`, and 1 more)                                        | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `search`              | README.md, docs/credits-and-acknowledgements.md, docs/drivers-and-logging.md, docs/overview.md, docs/search.md                                                                                                                                                                                                        | commands 1; providers 2; registries 6; config 24; migrations 1; actions 9; extension points 3               | registries: 6/6 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `RenderHookRegistry`, and 1 more)<br>config: 24/24 (`capell-search.body_column`, `capell-search.columns`, `capell-search.dashboard`, `capell-search.database`, `capell-search.default_days`, and 19 more)<br>actions: 9/9 (`BuildTopSearchesQueryAction`, `BuildTrendingSearchesQueryAction`, `BuildZeroResultSearchesQueryAction`, `NormalizeSearchQueryAction`, `PurgeSearchLogsAction`, and 4 more)<br>extension: 1/3 (`RegisterHeaderSearchHook`)                                                         | Keep driver binding, route config, logging, hashing, and retention in `docs/drivers-and-logging.md`.                                                                             |
| `seo-suite`           | README.md, docs/ai-discovery.md, docs/credits-and-acknowledgements.md, docs/extending-seo-suite.md, docs/overview.md, docs/publish-gates.md, docs/schema-templates.md, docs/search-console.md, docs/seo-intelligence.md, docs/seo-meta-and-discoverability.md, docs/sitemaps.md                                       | commands 5; providers 1; registries 7; config 67; migrations 11; actions 51; extension points 12            | registries: 7/7 (`CapellCore::registerModels`, `PageSchemaExtender::TAG`, `RenderHookRegistry`, `RenderHookRegistry`, `registerPageSchemaExtenders`, and 2 more)<br>config: 66/67 (`capell-seo-suite.CCBot`, `capell-seo-suite.ChatGPT-User`, `capell-seo-suite.Claude-SearchBot`, `capell-seo-suite.Claude-User`, `capell-seo-suite.ClaudeBot`, and 61 more)<br>actions: 46/51 (`ApplyAiDraftAction`, `BaseAction`, `BreadcrumbsSchemaAction`, `BuildAiDiscoveryPageEntriesAction`, `BuildAiDiscoveryPageQueryAction`, and 41 more)<br>extension: 1/12 (`RegisterSeoHeadHooks`)                                                                                                | Keep schema, AI Creator, content target, render hook, settings, and publish-report examples in `docs/extending-seo-suite.md`; keep crawler seed arrays out of public setup docs. |
| `site-discovery`      | README.md                                                                                                                                                                                                                                                                                                             | commands 1; providers 1; registries 1; config 0; migrations 0; actions 3; extension points 2                | registries: 1/1 (`CapellCore::registerModelInterceptor`)<br>actions: 3/3 (`DiscoverPublicPagesAction`, `DiscoverPublicUrlsAction`, `GenerateSitemapAction`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `tags`                | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 1; providers 3; registries 1; config 0; migrations 1; actions 0; extension points 0                | registries: 1/1 (`CapellCore::registerModels`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | Add a package docs page or README subsection for the listed code-only surfaces, starting with public extension points, commands, config, and actions called by other packages.   |
| `theme-agency`        | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 0; providers 0; registries 0; config 0; migrations 0; actions 0; extension points 0                | No code-only surface detected by string check.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | Keep current docs synchronized when adding new actions, commands, or registry calls.                                                                                             |
| `theme-corporate`     | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 0; providers 0; registries 0; config 0; migrations 0; actions 0; extension points 0                | No code-only surface detected by string check.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | Keep current docs synchronized when adding new actions, commands, or registry calls.                                                                                             |
| `theme-saas`          | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 0; providers 0; registries 0; config 0; migrations 0; actions 0; extension points 0                | No code-only surface detected by string check.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | Keep current docs synchronized when adding new actions, commands, or registry calls.                                                                                             |
| `translation-manager` | README.md, docs/overview.md, docs/sources-stores-and-ai.md                                                                                                                                                                                                                                                            | commands 0; providers 2; registries 1; config 10; migrations 0; actions 8; extension points 3               | registries: 1/1 (`CapellAdmin::registerExtensionPage`)<br>config: 10/10 (`capell-translation-manager.app_source`, `capell-translation-manager.key`, `capell-translation-manager.label`, `capell-translation-manager.locale_pattern`, `capell-translation-manager.package_paths`, and 5 more)<br>actions: 8/8 (`CreateLocaleFilesAction`, `DuplicateLocaleAction`, `ListInstalledLocalesAction`, `ListTranslationFilesAction`, and 4 more)                                                                                                                                                                                                                                       | Keep source resolver, file store, AI translator, locale config, and package write guidance in `docs/sources-stores-and-ai.md`.                                                   |
| `welcome-tour`        | README.md, docs/overview.md, docs/steps-and-settings.md                                                                                                                                                                                                                                                               | commands 0; providers 1; registries 2; config 10; migrations 0; actions 2; extension points 1               | registries: 2/2 (`CapellAdmin::registerWelcomeTourStep`, `registerSettingsClass`)<br>config: 10/10 (`capell-welcome-tour.description`, `capell-welcome-tour.element`, `capell-welcome-tour.enabled`, `capell-welcome-tour.icon`, `capell-welcome-tour.icon_color`, and 5 more)<br>actions: 2/2 (`CanShowWelcomeTourAction`, `SetUserWelcomeTourPreferenceAction`)                                                                                                                                                                                                                                                                                                               | Keep step config, programmatic step registration, dashboard replacement, and selector guidance in `docs/steps-and-settings.md`.                                                  |
| `wordpress-importer`  | README.md, docs/credits-and-acknowledgements.md, docs/overview.md                                                                                                                                                                                                                                                     | commands 0; providers 1; registries 0; config 0; migrations 0; actions 0; extension points 0                | No code-only surface detected by string check.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | Keep current docs synchronized when adding new actions, commands, or registry calls.                                                                                             |

## Detailed Inventory

<details>
<summary>Access Gate (capell-app/access-gate)</summary>

- Docs: README.md, docs/access-requests.md
- Commands: `capell:access-gate-doctor`, `capell:access-gate-install`, `capell:access-gate-setup`
- Service providers: `src/Providers/AccessGateServiceProvider.php`
- Registry calls: `CapellCore::registerModels`, `CapellCore::registerProtectedTable`
- Config keys: `access-gate.access_gate.area`, `access-gate.access_gate.browser_token`, `access-gate.aliases`, `access-gate.approval_limit`, `access-gate.approval_strategy`, `access-gate.browser_token`, `access-gate.claim_token_ttl_minutes`, `access-gate.connection`, `access-gate.cookies`, `access-gate.default`, `access-gate.default_area`, `access-gate.domain`, `access-gate.email`, `access-gate.enabled`, `access-gate.fields`, `access-gate.grant_duration_days`, `access-gate.http_only`, `access-gate.identity_methods`, `access-gate.identity_mode`, `access-gate.install`, `access-gate.key`, `access-gate.methods`, `access-gate.middleware`, `access-gate.name`, `access-gate.page_cache_aliases`, `access-gate.path`, `access-gate.registration`, `access-gate.registration_policy`, `access-gate.route_prefix`, `access-gate.same_site`, `access-gate.secure`, `access-gate.status`, `access-gate.status_endpoint_enabled`, `access-gate.token_policy`, `access-gate.ttl_minutes`
- Migrations: `2026_05_10_190838_01_create_access_gate_areas_table.php`, `2026_05_10_190838_02_create_access_gate_registrations_table.php`, `2026_05_10_190838_03_create_access_gate_grants_table.php`, `2026_05_10_190838_04_create_access_gate_claim_tokens_table.php`, `2026_05_10_190838_05_create_access_gate_browser_tokens_table.php`, `2026_05_10_190838_06_create_access_gate_events_table.php`, `2026_05_10_190838_07_add_site_id_to_access_gate_areas_table.php`, `2026_05_12_120000_08_add_schedule_to_access_gate_areas_table.php`, `2026_05_12_120001_09_add_download_resolver_indexes_to_access_gate_tables.php`
- Actions: `ApproveNextRegistrationsAction`, `ApproveRegistrationAction`, `AreaIsCurrentlyGatingAction`, `ConsumeAccessGateClaimTokenAction`, `CreateAccessGateBrowserTokenAction`, `CreateAccessGateClaimTokenAction`, `CreateAccessGateGrantAction`, `CreateRegistrationAction`, `EnsureAccessGateGrantCanIssueTokenAction`, `ExpireRegistrationAction`, `ListAccessRequestMethodsAction`, `RecordEventAction`, `RejectRegistrationAction`, `ResendAccessGateClaimTokenAction`, `ResolveAccessGateAccessAction`, `RevokeAccessGateBrowserTokenAction`, `RevokeAccessGateBrowserTokenRecordAction`, `RevokeAccessGateGrantAction`, `SendAccessGateApprovedNotificationAction`, `SetupDefaultAccessAreaAction`, `SubmitAccessGatePublicAction`, `UpdateAccessGateApprovalLimitAction`, `UpdateAccessGateAreaStatusAction`
- Public extension points: `AccessRequestMethod`, `RegistrationField`

Code-only gaps detected:

- registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerProtectedTable`)
- config: 26/35 (`access-gate.access_gate.area`, `access-gate.access_gate.browser_token`, `access-gate.aliases`, `access-gate.approval_limit`, `access-gate.approval_strategy`, and 21 more)
- actions: 20/23 (`ApproveNextRegistrationsAction`, `ApproveRegistrationAction`, `AreaIsCurrentlyGatingAction`, `ConsumeAccessGateClaimTokenAction`, `CreateAccessGateBrowserTokenAction`, and 15 more)

</details>

<details>
<summary>Address (capell-app/address)</summary>

- Docs: README.md, docs/address-api.md, docs/address-database.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: `capell:address-demo {--sites=}`, `capell:address-faker {--count=25} {--force}`, `capell:address-install`
- Service providers: `src/Providers/AddressServiceProvider.php`
- Registry calls: `CapellCore::registerModels`, `CapellCore::registerVendorAsset`
- Config keys: None found.
- Migrations: `2026_05_10_190839_01_create_countries_table.php`, `2026_05_10_190839_02_create_addresses_table.php`
- Actions: None found.
- Public extension points: None found.

Code-only gaps detected:

- registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerVendorAsset`)

</details>

<details>
<summary>Agent Bridge (capell-app/agent-bridge)</summary>

- Docs: README.md, docs/boost-integration.md, docs/capabilities.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/AgentBridgeServiceProvider.php`
- Registry calls: `CapellAdmin::registerAdminBridge`, `registerSettingsClass`
- Config keys: `capell-agent-bridge.confirmation_ttl_minutes`, `capell-agent-bridge.enable_user_resource_bridge`, `capell-agent-bridge.home`, `capell-agent-bridge.knowledge`, `capell-agent-bridge.public_docs_paths`, `capell-agent-bridge.routes`, `capell-agent-bridge.site`, `capell-agent-bridge.site_auth_guard`, `capell-agent-bridge.token_prefix`
- Migrations: `2026_05_10_190840_01_create_capell_agent-bridge_tokens_table.php`, `2026_05_10_190840_02_create_capell_agent-bridge_confirmations_table.php`, `2026_05_10_190840_03_create_capell_agent-bridge_audit_entries_table.php`
- Actions: `AuditAgentBridgeCapabilityAction`, `ClearCapellCacheCapabilityAction`, `ConfirmAgentBridgeCapabilityAction`, `CreateAgentBridgeTokenAction`, `CreateDraftPageCapabilityAction`, `DisablePageCapabilityAction`, `InspectPagePublishingReadinessCapabilityAction`, `InvokeAgentBridgeCapabilityPreviewAction`, `UpdateDraftPageCapabilityAction`
- Public extension points: `AgentBridgeSettings`, `AgentBridgeUserSchemaExtender`, `CapellAgentBridge`, `CapellAgentBridgeCapabilityAction`, `CapellAgentBridgeCapabilityProvider`

Code-only gaps detected:

- registries: 2/2 (`CapellAdmin::registerAdminBridge`, `registerSettingsClass`)
- config: 9/9 (`capell-agent-bridge.confirmation_ttl_minutes`, `capell-agent-bridge.enable_user_resource_bridge`, `capell-agent-bridge.home`, `capell-agent-bridge.knowledge`, `capell-agent-bridge.public_docs_paths`, and 4 more)
- actions: 9/9 (`AuditAgentBridgeCapabilityAction`, `ClearCapellCacheCapabilityAction`, `ConfirmAgentBridgeCapabilityAction`, `CreateAgentBridgeTokenAction`, `CreateDraftPageCapabilityAction`, and 4 more)
- extension: 1/5 (`AgentBridgeUserSchemaExtender`)

</details>

<details>
<summary>AI Orchestrator (capell-app/ai-orchestrator)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/AIOrchestratorServiceProvider.php`
- Registry calls: None found.
- Config keys: None found.
- Migrations: None found.
- Actions: `ListAIOrchestratorCapabilitiesAction`, `RegisterAIOrchestratorModuleAction`, `RunAIOrchestratorCapabilityAction`
- Public extension points: `AIOrchestratorModule`, `AIOrchestratorProviderConnector`

</details>

<details>
<summary>Blog (capell-app/blog)</summary>

- Docs: README.md, docs/blog-api.md, docs/blog-database.md, docs/credits-and-acknowledgements.md, docs/media-attachment.md, docs/overview.md
- Commands: `capell:blog-create-pages {site : The ID of the site to create blog pages for}`, `capell:blog-demo {--sites=} {--user=} {--limit=}`, `capell:blog-faker {--count=25} {--sites=} {--languages=} {--force}`, `capell:blog-install`, `capell:blog-setup {--user= : Ignored - accepted for compatibility with capell:install} {--sites= : Ignored - accepted for compatibility with capell:install} {--languages= : Ignored - accepted for compatibility with capell:install} {--url= : Ignored - accepted for compatibility with capell:install}`, `capell:hero-demo {--sites=}`
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/BlogServiceProvider.php`, `src/Providers/ConsoleServiceProvider.php`, `src/Providers/FrontendServiceProvider.php`
- Registry calls: `CapellCore::registerComponents`, `CapellCore::registerModelRelations`, `CapellCore::registerModels`, `CapellCore::registerPageType`, `CapellCore::registerPageVariation`, `CapellCore::registerVendorAsset`, `RenderHookRegistry`
- Config keys: None found.
- Migrations: `2026_05_10_190842_01_create_articles_table.php`
- Actions: `CreateBlogHeroDemoContentAction`, `CreateBlogPagesAction`, `EnsureArticlePublishingDefaultsAction`, `EnsureBlogPublishingSurfaceAction`, `GenerateArchiveUrl`, `GetArticleLayoutAction`, `InstallPackageAction`
- Public extension points: None found.

Code-only gaps detected:

- registries: 7/7 (`CapellCore::registerComponents`, `CapellCore::registerModelRelations`, `CapellCore::registerModels`, `CapellCore::registerPageType`, `CapellCore::registerPageVariation`, and 2 more)
- actions: 7/7 (`CreateBlogHeroDemoContentAction`, `CreateBlogPagesAction`, `EnsureArticlePublishingDefaultsAction`, `EnsureBlogPublishingSurfaceAction`, `GenerateArchiveUrl`, and 2 more)

</details>

<details>
<summary>Campaign Studio (capell-app/campaign-studio)</summary>

- Docs: README.md, docs/campaign-studio-api.md, docs/campaign-studio-database.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: `capell:campaign-studio-install-layouts {--force : Update existing campaign layouts}`
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/CampaignStudioServiceProvider.php`, `src/Providers/FrontendServiceProvider.php`
- Registry calls: `CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerNavigationGroup`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerComponents`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `CapellCore::registerVendorAsset`, `PageSchemaExtender::TAG`
- Config keys: `capell-campaign-studio.conversion_cookie`, `capell-campaign-studio.conversion_goals`, `capell-campaign-studio.conversions`, `capell-campaign-studio.cta_blocks`, `capell-campaign-studio.enabled`, `capell-campaign-studio.groups`, `capell-campaign-studio.landing_pages`, `capell-campaign-studio.layout_presets`, `capell-campaign-studio.tables`, `capell-campaign-studio.utm_keys`
- Migrations: `2026_05_10_190843_01_create_campaign_groups_table.php`, `2026_05_10_190843_02_create_campaign_conversion_goals_table.php`, `2026_05_10_190843_03_create_campaign_landing_pages_table.php`, `2026_05_10_190843_04_create_campaign_cta_blocks_table.php`, `2026_05_10_190843_05_create_campaign_conversions_table.php`
- Actions: `ApplyCampaignPageDefaultsAction`, `BuildCampaignConversionFunnelAction`, `BuildCampaignOverviewStatsAction`, `BuildCampaignUrlAction`, `BuildConversionAttributionAction`, `BuildTopCampaignStudioQueryAction`, `BuildTopLandingPagesQueryAction`, `InstallCampaignLayoutsAction`, `RecordCampaignConversionAction`, `RecordCtaClickConversionAction`, `RecordFormSubmissionConversionAction`, `RecordPageViewConversionAction`, `ResolveCampaignFromUrlAction`, `ResolveCampaignLandingPageFromUrlAction`, `SyncCampaignLandingPageFromPageAction`
- Public extension points: None found.

Code-only gaps detected:

- registries: 8/8 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerNavigationGroup`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerComponents`, `CapellCore::registerModels`, and 3 more)
- config: 10/10 (`capell-campaign-studio.conversion_cookie`, `capell-campaign-studio.conversion_goals`, `capell-campaign-studio.conversions`, `capell-campaign-studio.cta_blocks`, `capell-campaign-studio.enabled`, and 5 more)
- actions: 15/15 (`ApplyCampaignPageDefaultsAction`, `BuildCampaignConversionFunnelAction`, `BuildCampaignOverviewStatsAction`, `BuildCampaignUrlAction`, `BuildConversionAttributionAction`, and 10 more)

</details>

<details>
<summary>Content Sections (capell-app/content-sections)</summary>

- Docs: README.md
- Commands: None found.
- Service providers: `src/Providers/ContentSectionsServiceProvider.php`
- Registry calls: `CapellAdmin::registerAsset`, `CapellCore::registerAsset`, `CapellCore::registerModels`, `CapellCore::registerPageType`
- Config keys: `capell-content-sections.assets`, `capell-content-sections.color`, `capell-content-sections.icon`, `capell-content-sections.model`, `capell-content-sections.section`
- Migrations: `2026_05_10_190844_01_create_sections_table.php`
- Actions: `BuildSectionDemoDataAction`, `CreateContentAction`, `CreateHeroContentTypeAction`, `EnsureSectionTypeForKeyAction`, `ModifyContentSelectCreateAction`, `MutateContentDataBeforeFillAction`, `RegisterDefaultSectionsAction`, `RegisterSectionDefinitionProviderAction`, `ReplicateContentAction`, `ResolveRequestedSectionTypeAction`, `ResolveSectionComponentAction`
- Public extension points: `SectionDefinitionProvider`

Code-only gaps detected:

- registries: 4/4 (`CapellAdmin::registerAsset`, `CapellCore::registerAsset`, `CapellCore::registerModels`, `CapellCore::registerPageType`)
- config: 5/5 (`capell-content-sections.assets`, `capell-content-sections.color`, `capell-content-sections.icon`, `capell-content-sections.model`, `capell-content-sections.section`)
- actions: 11/11 (`BuildSectionDemoDataAction`, `CreateContentAction`, `CreateHeroContentTypeAction`, `EnsureSectionTypeForKeyAction`, `ModifyContentSelectCreateAction`, and 6 more)

</details>

<details>
<summary>Dashboard Reports (capell-app/dashboard-reports)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md
- Commands: None found.
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/DashboardReportsServiceProvider.php`
- Registry calls: `CapellAdmin::registerDashboardWidget`
- Config keys: None found.
- Migrations: None found.
- Actions: `BuildDefaultContentHealthAction`, `BuildPublishingTrendAction`
- Public extension points: None found.

Code-only gaps detected:

- registries: 1/1 (`CapellAdmin::registerDashboardWidget`)
- actions: 2/2 (`BuildDefaultContentHealthAction`, `BuildPublishingTrendAction`)

</details>

<details>
<summary>Demo Kit (capell-app/demo-kit)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md
- Commands: `capell:admin-demo {--user=} {--languages=} {--url=} {--sites=}`, `capell:demo {--user} {--languages=} {--packages} {--sites=} {--url} {--force}`, `capell:demo-kit-full-demo {--url=} {--user=} {--languages=} {--sites=} {--force}`
- Service providers: `src/Providers/DemoKitServiceProvider.php`
- Registry calls: `CapellAdmin::registerExtensionPage`
- Config keys: `capell-demo-kit.archive`, `capell-demo-kit.checksum`, `capell-demo-kit.children`, `capell-demo-kit.code`, `capell-demo-kit.color`, `capell-demo-kit.contents`, `capell-demo-kit.countries`, `capell-demo-kit.de`, `capell-demo-kit.default`, `capell-demo-kit.en`, `capell-demo-kit.es`, `capell-demo-kit.flag`, `capell-demo-kit.fr`, `capell-demo-kit.iso`, `capell-demo-kit.it`, `capell-demo-kit.key`, `capell-demo-kit.languages`, `capell-demo-kit.locale`, `capell-demo-kit.max_bytes`, `capell-demo-kit.name`, `capell-demo-kit.order`, `capell-demo-kit.pages`, `capell-demo-kit.url`
- Migrations: None found.
- Actions: `InsertExampleSiteDataAction`
- Public extension points: None found.

Code-only gaps detected:

- registries: 1/1 (`CapellAdmin::registerExtensionPage`)
- config: 23/23 (`capell-demo-kit.archive`, `capell-demo-kit.checksum`, `capell-demo-kit.children`, `capell-demo-kit.code`, `capell-demo-kit.color`, and 18 more)
- actions: 1/1 (`InsertExampleSiteDataAction`)

</details>

<details>
<summary>Deployments (capell-app/deployments)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md, docs/settings-and-ip-resolution.md
- Commands: None found.
- Service providers: `src/Providers/DeploymentsServiceProvider.php`
- Registry calls: `CapellAdmin::registerExtensionPage`
- Config keys: `capell-deployments.bitbucket`, `capell-deployments.client_id`, `capell-deployments.client_secret`, `capell-deployments.enabled`, `capell-deployments.github`, `capell-deployments.gitlab`, `capell-deployments.oauth`
- Migrations: `2026_05_10_190845_01_create_deployment_connections_table.php`
- Actions: `ConnectDeploymentAction`, `CreateOAuthStateAction`, `PrepareComposerRequirementCommitAction`, `PublishComposerRequirementAction`, `ValidateOAuthStateAction`
- Public extension points: `GitProviderContract`, `PublishesComposerChanges`

Code-only gaps detected:

- registries: 1/1 (`CapellAdmin::registerExtensionPage`)
- config: 7/7 (`capell-deployments.bitbucket`, `capell-deployments.client_id`, `capell-deployments.client_secret`, `capell-deployments.enabled`, `capell-deployments.github`, and 2 more)
- actions: 5/5 (`ConnectDeploymentAction`, `CreateOAuthStateAction`, `PrepareComposerRequirementCommitAction`, `PublishComposerRequirementAction`, `ValidateOAuthStateAction`)

</details>

<details>
<summary>Diagnostics (capell-app/diagnostics)</summary>

- Docs: README.md, docs/command-palette.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/DiagnosticsServiceProvider.php`
- Registry calls: `CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `PageSchemaExtender::TAG`
- Config keys: None found.
- Migrations: `2026_05_10_190846_01_create_command_palette_runs_table.php`
- Actions: `BuildCacheHealthAction`, `BuildConfigDriftAction`, `BuildContentGraphHealthAction`, `BuildMigrationsHealthAction`, `BuildPackagesInstalledAction`, `BuildPermissionAuditQueryAction`, `BuildQueueHealthQueryAction`, `BuildRegistryHealthAction`, `BuildSetupHealthAction`, `BuildTailwindBuildStatusAction`, `DiscoverCommandPaletteCommandsAction`, `EnsureDiagnosticsPermissionsAction`, `ExecuteCommandPaletteCommandAction`, `SummarizeFailedJobExceptionAction`, `ValidateCommandPaletteParametersAction`
- Public extension points: `CommandPaletteProvider`

Code-only gaps detected:

- registries: 3/3 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `PageSchemaExtender::TAG`)
- actions: 15/15 (`BuildCacheHealthAction`, `BuildConfigDriftAction`, `BuildContentGraphHealthAction`, `BuildMigrationsHealthAction`, `BuildPackagesInstalledAction`, and 10 more)

</details>

<details>
<summary>Email Studio (capell-app/email-studio)</summary>

- Docs: README.md, docs/email-studio-api.md, docs/email-studio-database.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/EmailStudioServiceProvider.php`, `src/Providers/FrontendServiceProvider.php`
- Registry calls: `CapellCore::registerModels`, `CapellCore::registerProtectedTable`
- Config keys: `capell-email-studio.body_retention_days`, `capell-email-studio.default_provider`, `capell-email-studio.events`, `capell-email-studio.messages`, `capell-email-studio.profiles`, `capell-email-studio.public_route_prefix`, `capell-email-studio.queue`, `capell-email-studio.recipients`, `capell-email-studio.replies`, `capell-email-studio.suppressions`, `capell-email-studio.tables`, `capell-email-studio.template_registrations`, `capell-email-studio.template_variants`, `capell-email-studio.templates`, `capell-email-studio.track_clicks`, `capell-email-studio.track_opens`, `capell-email-studio.tracking_rate_limit`, `capell-email-studio.tracking_token_ttl_days`, `capell-email-studio.tracking_tokens`, `capell-email-studio.webhook_rate_limit`, `capell-email-studio.webhook_tolerance_seconds`
- Migrations: `2026_05_10_190847_01_create_email_profiles_table.php`, `2026_05_10_190847_02_create_email_templates_table.php`, `2026_05_10_190847_03_create_email_template_variants_table.php`, `2026_05_10_190847_04_create_email_messages_table.php`, `2026_05_10_190847_05_create_email_recipients_table.php`, `2026_05_10_190847_06_create_email_events_table.php`, `2026_05_10_190847_07_create_email_replies_table.php`, `2026_05_10_190847_08_create_email_suppressions_table.php`, `2026_05_10_190847_09_create_email_template_registrations_table.php`, `2026_05_10_190847_10_create_email_tracking_tokens_table.php`
- Actions: `CheckEmailSuppressionAction`, `DeliverEmailMessageAction`, `RegisterEmailTemplateAction`, `RenderEmailTemplateAction`, `ResolveEmailTemplateVariantAction`, `SendEmailAction`, `SuppressEmailAddressAction`
- Public extension points: `EmailProviderAdapter`

Code-only gaps detected:

- registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerProtectedTable`)
- config: 21/21 (`capell-email-studio.body_retention_days`, `capell-email-studio.default_provider`, `capell-email-studio.events`, `capell-email-studio.messages`, `capell-email-studio.profiles`, and 16 more)

</details>

<details>
<summary>Events (capell-app/events)</summary>

- Docs: README.md
- Commands: `capell:events-install`
- Service providers: `src/Providers/EventsServiceProvider.php`
- Registry calls: `CapellAdmin::registerExtensionPage`, `CapellCore::registerModels`, `CapellCore::registerPageType`, `CapellCore::registerPageVariation`, `CapellCore::registerVendorAsset`, `RenderHookRegistry`, `RenderHookRegistry`
- Config keys: None found.
- Migrations: `2026_05_10_190848_01_create_event_venues_table.php`, `2026_05_10_190848_02_create_events_table.php`, `2026_05_10_190848_03_create_event_occurrences_table.php`, `2026_05_10_190848_04_create_event_registrations_table.php`, `2026_05_10_190848_05_create_event_notification_logs_table.php`
- Actions: `BuildCalendarFeedAction`, `BuildEventSchemaAction`, `CancelOccurrenceAction`, `EnsureEventPublishingDefaultsAction`, `EnsureEventPublishingSurfaceAction`, `ExpandEventRecurrenceAction`, `InstallPackageAction`, `PromoteWaitlistAction`, `QueryPublicEventOccurrencesAction`, `RegisterForEventOccurrenceAction`, `RescheduleOccurrenceAction`, `ScheduleEventNotificationsAction`, `SendEventNotificationAction`, `SyncEventOccurrencesAction`, `UpdateRegistrationStatusAction`
- Public extension points: `EventBookingProvider`, `EventRegistrationProvider`, `RegisterEventSchemaHooks`

Code-only gaps detected:

- registries: 7/7 (`CapellAdmin::registerExtensionPage`, `CapellCore::registerModels`, `CapellCore::registerPageType`, `CapellCore::registerPageVariation`, `CapellCore::registerVendorAsset`, and 2 more)
- actions: 15/15 (`BuildCalendarFeedAction`, `BuildEventSchemaAction`, `CancelOccurrenceAction`, `EnsureEventPublishingDefaultsAction`, `EnsureEventPublishingSurfaceAction`, and 10 more)
- extension: 1/3 (`RegisterEventSchemaHooks`)

</details>

<details>
<summary>Form Builder (capell-app/form-builder)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/FormBuilderServiceProvider.php`
- Registry calls: `CapellCore::registerModels`, `CapellCore::registerVendorAsset`
- Config keys: `capell-form-builder.collect_ip_address`, `capell-form-builder.collect_user_agent`, `capell-form-builder.store_submissions`
- Migrations: `2026_05_10_190849_01_create_form-builder_table.php`, `2026_05_10_190849_02_create_submissions_table.php`
- Actions: `ArchiveSubmissionAction`, `BuildFormValidationRulesAction`, `CreateSubmissionAction`, `MarkSubmissionReadAction`, `MarkSubmissionSpamAction`
- Public extension points: None found.

Code-only gaps detected:

- registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerVendorAsset`)
- config: 3/3 (`capell-form-builder.collect_ip_address`, `capell-form-builder.collect_user_agent`, `capell-form-builder.store_submissions`)
- actions: 5/5 (`ArchiveSubmissionAction`, `BuildFormValidationRulesAction`, `CreateSubmissionAction`, `MarkSubmissionReadAction`, `MarkSubmissionSpamAction`)

</details>

<details>
<summary>Foundation Theme (capell-app/foundation-theme)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: `capell:foundation-theme-setup {--force : Rebuild Foundation-managed layout defaults}`, `capell:frontend-tailwind-assets {--report : Print the aggregated assets report instead of writing files} {--output-path= : Absolute path or directory for the generated frontend CSS entrypoint}`
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/FoundationThemeServiceProvider.php`
- Registry calls: `CapellCore::registerModelInterceptor`, `CapellCore::registerVendorAsset`, `registerSettingsClass`
- Config keys: `capell-foundation-theme.asset_build_tool`, `capell-foundation-theme.autoprefixer`, `capell-foundation-theme.fontaine`, `capell-foundation-theme.imports`, `capell-foundation-theme.laravel-vite-plugin`, `capell-foundation-theme.local_storage_url`, `capell-foundation-theme.npm-run-all`, `capell-foundation-theme.npm_dependencies`, `capell-foundation-theme.output_css`, `capell-foundation-theme.plugins`, `capell-foundation-theme.site_base_url`, `capell-foundation-theme.sources`, `capell-foundation-theme.swiper`, `capell-foundation-theme.tailwind`, `capell-foundation-theme.tailwindcss`, `capell-foundation-theme.tippy.js`, `capell-foundation-theme.use_site_domain_for_media`, `capell-foundation-theme.validate_sources`, `capell-foundation-theme.vanilla-lazyload`, `capell-foundation-theme.vite`
- Migrations: None found.
- Actions: `InstallFoundationThemeLayoutDefaultsAction`
- Public extension points: `FoundationThemeSettings`, `FoundationThemeSettingsMigrationProvider`

Code-only gaps detected:

- registries: 3/3 (`CapellCore::registerModelInterceptor`, `CapellCore::registerVendorAsset`, `registerSettingsClass`)
- config: 20/20 (`capell-foundation-theme.asset_build_tool`, `capell-foundation-theme.autoprefixer`, `capell-foundation-theme.fontaine`, `capell-foundation-theme.imports`, `capell-foundation-theme.laravel-vite-plugin`, and 15 more)
- actions: 1/1 (`InstallFoundationThemeLayoutDefaultsAction`)

</details>

<details>
<summary>Frontend Authoring (capell-app/frontend-authoring)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/editable-regions.md, docs/in-page-editing.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/FrontendAuthoringServiceProvider.php`
- Registry calls: None found.
- Config keys: `capell-frontend-authoring.enabled`, `capell-frontend-authoring.page_content`, `capell-frontend-authoring.page_title`, `capell-frontend-authoring.selectors`
- Migrations: None found.
- Actions: `BuildEditableRegionManifestAction`, `ClearAffectedCachedUrlsAction`, `CollectAffectedCachedUrlsAction`, `UpdateEditableRegionAction`
- Public extension points: None found.

Code-only gaps detected:

- config: 2/4 (`capell-frontend-authoring.page_content`, `capell-frontend-authoring.page_title`)
- actions: 2/4 (`BuildEditableRegionManifestAction`, `CollectAffectedCachedUrlsAction`)

</details>

<details>
<summary>Frontend Optimizer (capell-app/frontend-optimizer)</summary>

- Docs: README.md
- Commands: None found.
- Service providers: `src/Providers/FrontendOptimizerServiceProvider.php`
- Registry calls: None found.
- Config keys: `capell-frontend-optimizer.critical_css`, `capell-frontend-optimizer.enabled`, `capell-frontend-optimizer.height`, `capell-frontend-optimizer.manifests`, `capell-frontend-optimizer.node_binary`, `capell-frontend-optimizer.paths`, `capell-frontend-optimizer.playwright`, `capell-frontend-optimizer.scope`, `capell-frontend-optimizer.script`, `capell-frontend-optimizer.timeout`, `capell-frontend-optimizer.viewports`, `capell-frontend-optimizer.width`
- Migrations: `2026_05_10_190851_01_create_frontend_optimizer_tables.php`
- Actions: `GenerateCriticalCssAction`, `PersistRenderProfileAction`, `PrepareRenderProfileAction`, `RenderProfileAssetsAction`, `ResolveOptimizationScopeAction`, `ResolveRenderProfileAction`, `StoreRenderProfileManifestAction`
- Public extension points: `CriticalCssGenerator`

Code-only gaps detected:

- config: 12/12 (`capell-frontend-optimizer.critical_css`, `capell-frontend-optimizer.enabled`, `capell-frontend-optimizer.height`, `capell-frontend-optimizer.manifests`, `capell-frontend-optimizer.node_binary`, and 7 more)
- actions: 7/7 (`GenerateCriticalCssAction`, `PersistRenderProfileAction`, `PrepareRenderProfileAction`, `RenderProfileAssetsAction`, `ResolveOptimizationScopeAction`, and 2 more)

</details>

<details>
<summary>GA4 Reports (capell-app/ga4-reports)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/data-client.md
- Commands: `ga4-reports:sync`
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/GA4ReportsServiceProvider.php`
- Registry calls: `CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `registerSettingsClass`
- Config keys: `capell-ga4-reports.credentials_path`, `capell-ga4-reports.daily_metrics`, `capell-ga4-reports.enabled`, `capell-ga4-reports.page_metrics`, `capell-ga4-reports.property_id`, `capell-ga4-reports.route_slug`, `capell-ga4-reports.sync_days`, `capell-ga4-reports.sync_runs`, `capell-ga4-reports.tables`
- Migrations: `2026_05_10_190852_01_create_ga4_reports_daily_metrics_table.php`, `2026_05_10_190852_02_create_ga4_reports_page_metrics_table.php`, `2026_05_10_190852_03_create_ga4_reports_sync_runs_table.php`
- Actions: `BuildGA4ReportsOverviewAction`, `BuildGA4ReportsTrendAction`, `BuildGA4ReportsWindowAction`, `BuildTopGA4ReportsPagesAction`, `PersistGA4ReportsDailyMetricAction`, `PersistGA4ReportsPageMetricAction`, `ResolveGA4ReportsConfigAction`, `SyncGA4ReportsMetricsAction`
- Public extension points: `GA4ReportsDataClientInterface`, `GA4ReportsSettings`, `GA4ReportsSettingsMigrationProvider`

Code-only gaps detected:

- registries: 6/6 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, and 1 more)
- config: 9/9 (`capell-ga4-reports.credentials_path`, `capell-ga4-reports.daily_metrics`, `capell-ga4-reports.enabled`, `capell-ga4-reports.page_metrics`, `capell-ga4-reports.property_id`, and 4 more)
- actions: 8/8 (`BuildGA4ReportsOverviewAction`, `BuildGA4ReportsTrendAction`, `BuildGA4ReportsWindowAction`, `BuildTopGA4ReportsPagesAction`, `PersistGA4ReportsDailyMetricAction`, and 3 more)

</details>

<details>
<summary>Hero (capell-app/hero)</summary>

- Docs: README.md
- Commands: `capell:hero-setup {--force : Rebuild Hero-managed home layout defaults}`
- Service providers: `src/Providers/HeroServiceProvider.php`
- Registry calls: `CapellCore::registerVendorAsset`
- Config keys: None found.
- Migrations: None found.
- Actions: `InstallHeroLayoutDefaultsAction`
- Public extension points: None found.

Code-only gaps detected:

- registries: 1/1 (`CapellCore::registerVendorAsset`)
- actions: 1/1 (`InstallHeroLayoutDefaultsAction`)

</details>

<details>
<summary>HTML Cache (capell-app/html-cache)</summary>

- Docs: README.md, docs/cache-invalidation.md
- Commands: `capell:static-site {--site=} {--internal : Render URLs through the current Laravel kernel} {--refresh : Delete affected HTML cache files before rendering}`
- Service providers: `src/Providers/HtmlCacheServiceProvider.php`
- Registry calls: `CapellAdmin::registerAdminBridge`
- Config keys: `capell-html-cache.cache_skip_authenticated`, `capell-html-cache.cache_ttl`, `capell-html-cache.cache_vary_headers`, `capell-html-cache.enabled`, `capell-html-cache.internal_requests`, `capell-html-cache.minify_html`, `capell-html-cache.model_event_registration_mode`, `capell-html-cache.public_html_authoring_markers`, `capell-html-cache.site_health_cached_url_limit`, `capell-html-cache.site_health_public_html_scan_limit`, `capell-html-cache.site_health_unindexed_public_html_scan_limit`, `capell-html-cache.static_generation`, `capell-html-cache.write_enabled`
- Migrations: `2026_05_10_190854_01_create_cached_model_urls_table.php`
- Actions: `BuildCacheMapOverviewAction`, `BuildCachedModelUrlDiagnosticsAction`, `BuildHtmlCachePublicOutputSafetyDiagnosticsAction`, `ClearAllHtmlCacheAction`, `ClearCachedPageUrlsAction`, `ClearCachedUrlAction`, `ClearCachedUrlsForModelAction`, `DeletePageCacheAction`, `EnsureHtmlCachePermissionsAction`, `GenerateStaticSiteAction`, `GenerateStaticSitesAction`, `ListCacheMapResourceOptionsAction`, `NotifyClearCachedPagesAction`, `RecordCachedModelUrlsAction`
- Public extension points: `PageCacheNotifiable`

Code-only gaps detected:

- registries: 1/1 (`CapellAdmin::registerAdminBridge`)
- config: 5/13 (`capell-html-cache.cache_vary_headers`, `capell-html-cache.internal_requests`, `capell-html-cache.site_health_cached_url_limit`, `capell-html-cache.site_health_public_html_scan_limit`, `capell-html-cache.site_health_unindexed_public_html_scan_limit`)
- actions: 8/14 (`BuildCacheMapOverviewAction`, `BuildCachedModelUrlDiagnosticsAction`, `BuildHtmlCachePublicOutputSafetyDiagnosticsAction`, `ClearAllHtmlCacheAction`, `DeletePageCacheAction`, and 3 more)

</details>

<details>
<summary>Insights (capell-app/insights)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: `insights:purge {--days= : Override insights retention days}`
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/InsightsServiceProvider.php`
- Registry calls: `CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `RenderHookRegistry`, `RenderHookRegistry`, `registerSettingsClass`
- Config keys: `capell-insights.automatic_click_tracking`, `capell-insights.consents`, `capell-insights.default_consent_region`, `capell-insights.enabled`, `capell-insights.events`, `capell-insights.hash_salt`, `capell-insights.hash_visitor_data`, `capell-insights.ignored_paths`, `capell-insights.ignored_selectors`, `capell-insights.policy_version`, `capell-insights.require_consent_for_all_regions`, `capell-insights.retention_days`, `capell-insights.route_prefix`, `capell-insights.tables`, `capell-insights.track_clicks`, `capell-insights.track_form-builder`, `capell-insights.track_page_views`, `capell-insights.visits`
- Migrations: `2026_05_10_190855_01_create_insights_visits_table.php`, `2026_05_10_190855_02_create_insights_consents_table.php`, `2026_05_10_190855_03_create_insights_events_table.php`, `2026_05_10_190855_04_add_insights_reporting_indexes.php`, `2026_05_10_190855_05_import_legacy_page_views.php`, `2026_05_10_190855_06_add_page_url_hit_columns.php`
- Actions: `BuildInsightsOverviewStatsAction`, `BuildJourneyTimelineAction`, `BuildLiveInsightsStatsAction`, `BuildPopularPagesQueryAction`, `BuildRecentJourneysQueryAction`, `BuildTopActionsQueryAction`, `BuildTrendingPagesQueryAction`, `CreateInsightsVisitAction`, `ImportLegacyPageViewsAction`, `PurgeInsightsDataAction`, `RecordClickAction`, `RecordCustomActionAction`, `RecordInsightsEventAction`, `RecordInsightsEventsAction`, `RecordPageViewAction`, `ResolveConsentRegionAction`, `UpdateInsightsConsentAction`
- Public extension points: `InsightsSettings`, `InsightsSettingsMigrationProvider`, `RegisterInsightsTrackerHook`

Code-only gaps detected:

- registries: 8/8 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, and 3 more)
- config: 18/18 (`capell-insights.automatic_click_tracking`, `capell-insights.consents`, `capell-insights.default_consent_region`, `capell-insights.enabled`, `capell-insights.events`, and 13 more)
- actions: 17/17 (`BuildInsightsOverviewStatsAction`, `BuildJourneyTimelineAction`, `BuildLiveInsightsStatsAction`, `BuildPopularPagesQueryAction`, `BuildRecentJourneysQueryAction`, and 12 more)
- extension: 1/3 (`RegisterInsightsTrackerHook`)

</details>

<details>
<summary>Login Audit (capell-app/login-audit)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/LoginAuditServiceProvider.php`
- Registry calls: `CapellAdmin::registerAdminBridge`, `CapellAdmin::registerDashboardWidget`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `registerSettingsClass`
- Config keys: `login-audit.behind_cdn`, `login-audit.db_connection`, `login-audit.enabled`, `login-audit.events`, `login-audit.failed`, `login-audit.failed-login`, `login-audit.http_header_field`, `login-audit.listeners`, `login-audit.location`, `login-audit.login`, `login-audit.logout`, `login-audit.new-device`, `login-audit.notifications`, `login-audit.other-device-logout`, `login-audit.purge`, `login-audit.table_name`, `login-audit.template`
- Migrations: `2026_05_10_190857_01_create_login_audit_table.php`
- Actions: `ApplyLoginAuditSettingsAction`, `BuildLoginAuditsQueryAction`, `ResolveLoginAuditIpAddressAction`, `ShouldTrackUserIpAddressesAction`
- Public extension points: `LoginAuditSettings`, `LoginAuditUserSchemaExtender`

Code-only gaps detected:

- registries: 5/5 (`CapellAdmin::registerAdminBridge`, `CapellAdmin::registerDashboardWidget`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `registerSettingsClass`)
- config: 17/17 (`login-audit.behind_cdn`, `login-audit.db_connection`, `login-audit.enabled`, `login-audit.events`, `login-audit.failed`, and 12 more)
- actions: 4/4 (`ApplyLoginAuditSettingsAction`, `BuildLoginAuditsQueryAction`, `ResolveLoginAuditIpAddressAction`, `ShouldTrackUserIpAddressesAction`)
- extension: 1/2 (`LoginAuditUserSchemaExtender`)

</details>

<details>
<summary>Media AI (capell-app/media-ai)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/MediaAIServiceProvider.php`
- Registry calls: None found.
- Config keys: `capell-media-ai.enabled`
- Migrations: None found.
- Actions: None found.
- Public extension points: `ImageDoctor`

Code-only gaps detected:

- config: 1/1 (`capell-media-ai.enabled`)

</details>

<details>
<summary>Media Library (capell-app/media-library)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: `capell:media-migrate-to-curator {--dry-run : Report what would happen without writing} {--collection=* : Spatie collection names to migrate (repeatable; default: all)} {--owner-type= : Restrict migration to this owner model FQCN} {--chunk=200 : Number of Spatie media rows to process per chunk}`
- Service providers: None found.
- Registry calls: `CapellAdmin::registerExtensionPage`, `CapellCore::registerModels`
- Config keys: None found.
- Migrations: None found.
- Actions: `BuildMediaHealthQueryAction`, `MigrateSpatieMediaToCuratorAction`
- Public extension points: None found.

Code-only gaps detected:

- registries: 2/2 (`CapellAdmin::registerExtensionPage`, `CapellCore::registerModels`)
- actions: 1/2 (`BuildMediaHealthQueryAction`)

</details>

<details>
<summary>Migration Assistant (capell-app/migration-assistant)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/import-export-workflow.md, docs/migration-assistant.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/MigrationAssistantServiceProvider.php`
- Registry calls: `CapellCore::registerModels`
- Config keys: `migration-assistant.channels`, `migration-assistant.completed`, `migration-assistant.connection`, `migration-assistant.disk`, `migration-assistant.enabled`, `migration-assistant.exports`, `migration-assistant.failed`, `migration-assistant.imports`, `migration-assistant.limits`, `migration-assistant.max_media_bytes`, `migration-assistant.max_metadata_json_bytes`, `migration-assistant.max_package_uncompressed_bytes`, `migration-assistant.max_payload_json_bytes`, `migration-assistant.name`, `migration-assistant.notifications`, `migration-assistant.paths`, `migration-assistant.queue`, `migration-assistant.recipients`, `migration-assistant.working`
- Migrations: `2026_05_10_190859_01_create_import_sessions_table.php`, `2026_05_10_190859_02_create_import_rollback_dashboard-dashboard_reports_table.php`
- Actions: `BuildImportValidationSummaryAction`, `BuildPageReviewRows`, `BuildRelationResolveRowsAction`, `CancelImportSessionAction`, `CreateImportRollbackReportAction`, `InstallMigrationAssistantPermissionsAction`, `RetryImportSessionAction`
- Public extension points: `ImportSessionSubNavigationExtender`, `ImportSourceReader`, `MigrationAssistantContextResolver`, `MigrationAssistantRowContributor`, `NullMigrationAssistantContextResolver`, `NullMigrationAssistantRowContributor`, `NullPageCollisionDetector`, `PageCollisionDetector`

Code-only gaps detected:

- registries: 1/1 (`CapellCore::registerModels`)
- config: 19/19 (`migration-assistant.channels`, `migration-assistant.completed`, `migration-assistant.connection`, `migration-assistant.disk`, `migration-assistant.enabled`, and 14 more)
- actions: 7/7 (`BuildImportValidationSummaryAction`, `BuildPageReviewRows`, `BuildRelationResolveRowsAction`, `CancelImportSessionAction`, `CreateImportRollbackReportAction`, and 2 more)

</details>

<details>
<summary>Navigation (capell-app/navigation)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md, docs/rendering-and-sync.md
- Commands: `capell:navigation-demo {--sites=} {--languages=}`, `capell:navigation-setup {--sites=}`
- Service providers: `src/Providers/NavigationServiceProvider.php`
- Registry calls: `CapellCore::registerModels`, `CapellCore::registerPageType`, `RenderHookRegistry`, `RenderHookRegistry`
- Config keys: None found.
- Migrations: `2026_05_10_190860_01_create_navigations_table.php`
- Actions: `AddPageToNavigationAction`, `BuildNavigationRenderModelAction`, `RemovePageFromNavigationAction`, `ReplicateSiteNavigationsAction`, `ResolveNavigationItemModelsAction`
- Public extension points: `NavigationNamesResolver`, `NavigationPageSyncer`, `RegisterFoundationHeaderNavigationHook`

Code-only gaps detected:

- registries: 4/4 (`CapellCore::registerModels`, `CapellCore::registerPageType`, `RenderHookRegistry`, `RenderHookRegistry`)
- actions: 5/5 (`AddPageToNavigationAction`, `BuildNavigationRenderModelAction`, `RemovePageFromNavigationAction`, `ReplicateSiteNavigationsAction`, `ResolveNavigationItemModelsAction`)
- extension: 1/3 (`RegisterFoundationHeaderNavigationHook`)

</details>

<details>
<summary>Newsletter (capell-app/newsletter)</summary>

- Docs: README.md, docs/subscription-workflow.md
- Commands: `newsletter:sync-retry-due {--limit= : Maximum number of due attempts to requeue}`
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/NewsletterServiceProvider.php`
- Registry calls: `CapellAdmin::registerNavigationGroup`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `registerSettingsClass`
- Config keys: `capell-newsletter.consent_events`, `capell-newsletter.default_confirmation_mode`, `capell-newsletter.double_opt_in`, `capell-newsletter.enabled_by_default`, `capell-newsletter.form_mappings`, `capell-newsletter.import_batches`, `capell-newsletter.newsletter_tag_type`, `capell-newsletter.provider_audiences`, `capell-newsletter.provider_connections`, `capell-newsletter.provider_interest_mappings`, `capell-newsletter.provider_subscribers`, `capell-newsletter.public_tokens`, `capell-newsletter.queue`, `capell-newsletter.resubscribe_policy`, `capell-newsletter.retry_minutes`, `capell-newsletter.segment_subscriber`, `capell-newsletter.segments`, `capell-newsletter.subscribers`, `capell-newsletter.sync`, `capell-newsletter.sync_attempts`, `capell-newsletter.tables`, `capell-newsletter.token_expiry_hours`
- Migrations: `2026_05_10_190861_01_create_newsletter_provider_connections_table.php`, `2026_05_10_190861_02_create_newsletter_subscribers_table.php`, `2026_05_10_190861_03_create_newsletter_provider_audiences_table.php`, `2026_05_10_190861_04_create_newsletter_consent_events_table.php`, `2026_05_10_190861_05_create_newsletter_provider_interest_mappings_table.php`, `2026_05_10_190861_06_create_newsletter_provider_subscribers_table.php`, `2026_05_10_190861_07_create_newsletter_public_tokens_table.php`, `2026_05_10_190861_08_create_newsletter_segments_table.php`, `2026_05_10_190861_09_create_newsletter_sync_attempts_table.php`, `2026_05_10_190861_10_create_newsletter_form_mappings_table.php`, `2026_05_10_190861_11_create_newsletter_import_batches_table.php`
- Actions: `ApplyNewsletterTagsAction`, `ConfirmSubscriberAction`, `CreateUnsubscribeTokenAction`, `EvaluateNewsletterSegmentAction`, `ExportSubscribersAction`, `HandleProviderWebhookAction`, `ImportSubscribersAction`, `ParseSubscriberCsvRowsAction`, `QueueProviderSyncAction`, `RecordConsentEventAction`, `RequestDoubleOptInAction`, `RequeueDueProviderSyncAttemptsAction`, `SubscribeFromFormSubmissionAction`, `SyncSubscriberToProviderAction`, `UnsubscribeSubscriberAction`, `UpsertSubscriberAction`
- Public extension points: `NewsletterAudienceProvider`, `NewsletterProviderAdapter`, `NewsletterSegmentProvider`, `NewsletterSettings`

Code-only gaps detected:

- registries: 5/5 (`CapellAdmin::registerNavigationGroup`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `registerSettingsClass`)
- config: 17/22 (`capell-newsletter.consent_events`, `capell-newsletter.default_confirmation_mode`, `capell-newsletter.enabled_by_default`, `capell-newsletter.form_mappings`, `capell-newsletter.import_batches`, and 12 more)
- actions: 9/16 (`CreateUnsubscribeTokenAction`, `EvaluateNewsletterSegmentAction`, `ExportSubscribersAction`, `HandleProviderWebhookAction`, `ImportSubscribersAction`, and 4 more)

</details>

<details>
<summary>Notes (capell-app/notes)</summary>

- Docs: README.md
- Commands: None found.
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/NotesServiceProvider.php`
- Registry calls: `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerUserMenuItem`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`
- Config keys: None found.
- Migrations: `2026_05_10_190862_01_create_notes_tables.php`
- Actions: `AssignNoteUsersAction`, `BuildUserAttentionCountsAction`, `CompleteNoteAssignmentAction`, `CreateNoteAction`, `MentionNoteUsersAction`, `ReopenNoteAction`, `ResolveNoteAction`
- Public extension points: `CapellNotes`

Code-only gaps detected:

- registries: 4/4 (`CapellAdmin::registerExtensionPage`, `CapellAdmin::registerUserMenuItem`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`)
- actions: 7/7 (`AssignNoteUsersAction`, `BuildUserAttentionCountsAction`, `CompleteNoteAssignmentAction`, `CreateNoteAction`, `MentionNoteUsersAction`, and 2 more)
- extension: 1/1 (`CapellNotes`)

</details>

<details>
<summary>Password Policy (capell-app/password-policy)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md
- Commands: None found.
- Service providers: `src/Providers/PasswordPolicyServiceProvider.php`
- Registry calls: `CapellAdmin::registerAdminBridge`, `CapellAdmin::registerExtensionPage`, `registerSettingsClass`
- Config keys: `capell-password-policy.enabled`
- Migrations: `2026_05_10_190863_01_add_password_policy_columns_to_users_table.php`, `2026_05_10_190863_02_create_password_policy_password_histories_table.php`
- Actions: `EvaluatePasswordPolicyAction`, `MarkUserForPasswordChangeAction`, `RecordPasswordHistoryAction`, `UpdatePasswordAction`, `ValidatePasswordChangeAction`
- Public extension points: `PasswordPolicySettings`

Code-only gaps detected:

- registries: 3/3 (`CapellAdmin::registerAdminBridge`, `CapellAdmin::registerExtensionPage`, `registerSettingsClass`)
- config: 1/1 (`capell-password-policy.enabled`)
- actions: 5/5 (`EvaluatePasswordPolicyAction`, `MarkUserForPasswordChangeAction`, `RecordPasswordHistoryAction`, `UpdatePasswordAction`, `ValidatePasswordChangeAction`)

</details>

<details>
<summary>Public Actions (capell-app/public-actions)</summary>

- Docs: README.md, docs/actions-and-integrations.md, docs/provider-presets.md
- Commands: None found.
- Service providers: `src/Providers/PublicActionsServiceProvider.php`
- Registry calls: `CapellCore::registerModels`, `CapellCore::registerProtectedTable`
- Config keys: `capell-public-actions.actions`, `capell-public-actions.adapter`, `capell-public-actions.adapters`, `capell-public-actions.allow_insecure_webhook_urls`, `capell-public-actions.allow_private_webhook_urls`, `capell-public-actions.api_rate_limit`, `capell-public-actions.api_route_prefix`, `capell-public-actions.destinations`, `capell-public-actions.dispatch_attempts`, `capell-public-actions.expects_json`, `capell-public-actions.form_builder`, `capell-public-actions.generic`, `capell-public-actions.integration_tokens`, `capell-public-actions.make`, `capell-public-actions.mappings`, `capell-public-actions.method`, `capell-public-actions.n8n`, `capell-public-actions.pipedream`, `capell-public-actions.presets`, `capell-public-actions.queue`, `capell-public-actions.route_prefix`, `capell-public-actions.submissions`, `capell-public-actions.submit_rate_limit`, `capell-public-actions.tables`, `capell-public-actions.webhook_timeout_seconds`, `capell-public-actions.zapier`
- Migrations: `2026_05_10_190865_01_create_public_actions_table.php`, `2026_05_10_190865_02_create_public_action_destinations_table.php`, `2026_05_10_190865_03_create_public_action_submissions_table.php`, `2026_05_10_190865_04_create_public_action_dispatch_attempts_table.php`, `2026_05_10_190865_05_create_public_action_integration_tokens_table.php`
- Actions: `BuildPublicActionIntegrationQueryAction`, `BuildZapierSubmissionPayloadAction`, `CreatePublicActionIntegrationTokenAction`, `DispatchPublicActionDestinationAction`, `ListPublicActionOptionsAction`, `ResolvePublicActionForIntegrationTokenAction`, `ResolvePublicActionIntegrationTokenAction`, `RevokePublicActionIntegrationTokenAction`, `SubmitPublicActionAction`
- Public extension points: `PublicActionDestinationAdapter`, `PublicActionHandler`

Code-only gaps detected:

- registries: 2/2 (`CapellCore::registerModels`, `CapellCore::registerProtectedTable`)
- config: 18/26 (`capell-public-actions.actions`, `capell-public-actions.adapter`, `capell-public-actions.adapters`, `capell-public-actions.api_rate_limit`, `capell-public-actions.destinations`, and 13 more)
- actions: 7/9 (`BuildPublicActionIntegrationQueryAction`, `BuildZapierSubmissionPayloadAction`, `ListPublicActionOptionsAction`, `ResolvePublicActionForIntegrationTokenAction`, `ResolvePublicActionIntegrationTokenAction`, and 2 more)

</details>

<details>
<summary>Publishing Studio (capell-app/publishing-studio)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/extending-publishing-studio.md, docs/overview.md, docs/page-creation-and-approval-flow.md, docs/page-drafts-and-publishing.md, docs/publishing-studio-draftable-contract.md, docs/publishing-studio.md, docs/publishing-workflow.md, docs/release-workspaces.md
- Commands: `capell:publishing-studio-install`, `capell:publishing-studio:load-test {--publishing-studio=10 : Number of publishing-studio to create} {--rows-per-workspace=100 : Fixture rows per workspace} {--fresh : Truncate the fixture workspace tables first} {--publish= : Publish the first N publishing-studio after populating (defaults to 0)} {--force : Allow running outside local/testing environments}`, `capell:publishing-studio:prune {--id=* : Prune a specific workspace id instead of every abandoned workspace} {--dry-run : Report what would be pruned without making changes}`
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/ConsoleServiceProvider.php`, `src/Providers/PublishingStudioServiceProvider.php`
- Registry calls: `CapellAdmin::registerAdminBridge`, `CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellPublishingStudio::subscribe`, `RenderHookRegistry`, `registerSettingsClass`
- Config keys: None found.
- Migrations: `2026_05_10_190866_01_create_preview_links_table.php`, `2026_05_10_190866_02_create_publishing-studio_table.php`, `2026_05_10_190866_03_create_versions_table.php`, `2026_05_10_190866_04_create_workspace_approvals_table.php`, `2026_05_10_190866_05_create_workspace_field_comments_table.php`, `2026_05_10_190866_06_create_workspace_review_assignments_table.php`, `2026_05_10_190866_07_seed_bootstrap_workspace_version.php`, `2026_05_10_190866_08_z_add_workspace_columns_to_core_tables.php`, `2026_05_10_190866_09_z_add_workspace_id_to_external_tables.php`, `2026_05_10_190866_10_z_add_workspace_id_to_import_sessions_table.php`
- Actions: `AdvancePageImportToValidationAction`, `BuildActivityTrailQueryAction`, `BuildContentHealthAction`, `BuildContentSchedulerEventsAction`, `BuildMyWorkQueueAction`, `BuildPublishingWorkflowAttentionItemsAction`, `BuildPublishingWorkflowCommandCenterAction`, `BuildRecentlyPublishedAction`, `BuildReleaseWorkspaceReadinessAction`, `BuildReleaseWorkspaceSummaryAction`, `BuildScheduledPublishingQueryAction`, `BuildSiteStatsAction`, `BuildStaleDraftsQueryAction`, `BuildWorkspaceActivityAction`, `BuildWorkspaceMergeHistoryAction`, `CopyOnWriteAction`, `CreatePageDraftWorkspaceAction`, `DeletePageDraftAction`, `DiscardPublishingStudioAction`, `DispatchPageImportAction`, `EnsurePublishingStudioPermissionsAction`, `ExtendPreviewLinkAction`, `GenerateWorkspacePreviewUrlAction`, `InstallWorkspaceRolesAction`, `InvalidatePublishedWorkspaceFrontendCacheAction`, `RefreshPageImportStatusAction`, `RequestReviewBulkAction`, `ResolvePageImportConfirmationTargetAction`, `ResolvePageImportSessionAction`, `RevokePreviewLinkAction`, `SetWorkspaceSchedulerMetadataAction`, `StartPageImportAction`
- Public extension points: `CapellPublishingStudio`, `PublishingStudioPageEditExtender`, `PublishingStudioPageExportExtender`, `PublishingStudioPageResourcePageExtender`, `PublishingStudioPageTableExtender`, `PublishingStudioSettings`, `PublishingStudioUserSchemaExtender`, `ReleaseWorkspaceItemContributor`, `WorkspaceTableActionContributor`

Code-only gaps detected:

- registries: 8/8 (`CapellAdmin::registerAdminBridge`, `CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerExtensionPage`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, and 3 more)
- actions: 31/32 (`AdvancePageImportToValidationAction`, `BuildActivityTrailQueryAction`, `BuildContentHealthAction`, `BuildContentSchedulerEventsAction`, `BuildMyWorkQueueAction`, and 26 more)
- extension: 6/9 (`CapellPublishingStudio`, `PublishingStudioPageEditExtender`, `PublishingStudioPageExportExtender`, `PublishingStudioPageResourcePageExtender`, `PublishingStudioPageTableExtender`, and 1 more)

</details>

<details>
<summary>Search (capell-app/search)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/drivers-and-logging.md, docs/overview.md, docs/search.md
- Commands: `search:purge {--days= : Override retention days}`
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/SearchServiceProvider.php`
- Registry calls: `CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `RenderHookRegistry`, `registerSettingsClass`
- Config keys: `capell-search.body_column`, `capell-search.columns`, `capell-search.dashboard`, `capell-search.database`, `capell-search.default_days`, `capell-search.driver`, `capell-search.enabled`, `capell-search.excerpt_column`, `capell-search.excerpt_length`, `capell-search.hash_visitor_data`, `capell-search.logs`, `capell-search.minimum_query_length`, `capell-search.model`, `capell-search.record_search_logs`, `capell-search.results_per_page`, `capell-search.retention_days`, `capell-search.route_path`, `capell-search.scout`, `capell-search.show_header_search`, `capell-search.table`, `capell-search.table_name`, `capell-search.title_column`, `capell-search.type_column`, `capell-search.url_column`
- Migrations: `2026_05_10_190868_01_create_search_logs_table.php`
- Actions: `BuildTopSearchesQueryAction`, `BuildTrendingSearchesQueryAction`, `BuildZeroResultSearchesQueryAction`, `NormalizeSearchQueryAction`, `PurgeSearchLogsAction`, `RecordSearchAction`, `RecordSearchResultClickAction`, `ResolveSearchSettingAction`, `RunSearchAction`
- Public extension points: `RegisterHeaderSearchHook`, `Search`, `SearchSettings`

Code-only gaps detected:

- registries: 6/6 (`CapellAdmin::registerDashboardWidget`, `CapellAdmin::registerOverviewStat`, `CapellCore::registerModels`, `CapellCore::registerProtectedTable`, `RenderHookRegistry`, and 1 more)
- config: 24/24 (`capell-search.body_column`, `capell-search.columns`, `capell-search.dashboard`, `capell-search.database`, `capell-search.default_days`, and 19 more)
- actions: 9/9 (`BuildTopSearchesQueryAction`, `BuildTrendingSearchesQueryAction`, `BuildZeroResultSearchesQueryAction`, `NormalizeSearchQueryAction`, `PurgeSearchLogsAction`, and 4 more)
- extension: 1/3 (`RegisterHeaderSearchHook`)

</details>

<details>
<summary>SEO Suite (capell-app/seo-suite)</summary>

- Docs: README.md, docs/ai-discovery.md, docs/credits-and-acknowledgements.md, docs/overview.md, docs/publish-gates.md, docs/schema-templates.md, docs/search-console.md, docs/seo-intelligence.md, docs/seo-meta-and-discoverability.md, docs/sitemaps.md
- Commands: `capell:admin-clear-ai-cache`, `capell:admin-monitor-ai-usage`, `capell:admin-test-openai`, `capell:seo-suite-install`, `capell:seo-suite-setup`
- Service providers: `src/Providers/SeoSuiteServiceProvider.php`
- Registry calls: `CapellCore::registerModels`, `PageSchemaExtender::TAG`, `RenderHookRegistry`, `RenderHookRegistry`, `registerPageSchemaExtenders`, `registerSchemaTemplateRegistry`, `registerSettingsClass`
- Config keys: `capell-seo-suite.CCBot`, `capell-seo-suite.ChatGPT-User`, `capell-seo-suite.Claude-SearchBot`, `capell-seo-suite.Claude-User`, `capell-seo-suite.ClaudeBot`, `capell-seo-suite.GPTBot`, `capell-seo-suite.Google-Extended`, `capell-seo-suite.OAI-SearchBot`, `capell-seo-suite.PerplexityBot`, `capell-seo-suite.ai_content_brief`, `capell-seo-suite.ai_creator`, `capell-seo-suite.ai_creator_clarify`, `capell-seo-suite.ai_creator_layout`, `capell-seo-suite.ai_discovery`, `capell-seo-suite.ai_image_generation`, `capell-seo-suite.cache`, `capell-seo-suite.canonical`, `capell-seo-suite.checks`, `capell-seo-suite.content_generation`, `capell-seo-suite.crawler_policy`, `capell-seo-suite.crawler_policy_presets`, `capell-seo-suite.credentials_path`, `capell-seo-suite.default_crawler_rules`, `capell-seo-suite.directive`, `capell-seo-suite.enabled`, `capell-seo-suite.features`, `capell-seo-suite.handler`, `capell-seo-suite.image_model`, `capell-seo-suite.image_provider`, `capell-seo-suite.image_size`, `capell-seo-suite.internal_links`, `capell-seo-suite.max_retries`, `capell-seo-suite.max_tokens`, `capell-seo-suite.meta_description`, `capell-seo-suite.meta_title`, `capell-seo-suite.model`, `capell-seo-suite.notes`, `capell-seo-suite.open`, `capell-seo-suite.path`, `capell-seo-suite.prism`, and 27 more
- Migrations: `2026_05_10_190870_01_create_ai_creator_contexts_table.php`, `2026_05_10_190870_02_create_ai_generation_histories_table.php`, `2026_05_10_190870_03_create_ai_creator_sessions_table.php`, `2026_05_10_190870_04_create_ai_discovery_crawler_rules_table.php`, `2026_05_10_190870_05_create_ai_discovery_page_profiles_table.php`, `2026_05_10_190870_06_create_ai_discovery_site_profiles_table.php`, `2026_05_10_190870_07_create_ai_discovery_snapshots_table.php`, `2026_05_10_190870_08_create_broken_links_table.php`, `2026_05_10_190870_09_create_page_seo_snapshots_table.php`, `2026_05_10_190870_10_create_search_console_url_metrics_table.php`, `2026_05_10_190870_11_remove_redirect_opportunities_count_from_page_seo_snapshots_table.php`
- Actions: `ApplyAiDraftAction`, `BaseAction`, `BreadcrumbsSchemaAction`, `BuildAiDiscoveryPageEntriesAction`, `BuildAiDiscoveryPageQueryAction`, `BuildAiReadinessAuditAction`, `BuildAiRobotsTxtRulesAction`, `BuildBrokenLinksQueryAction`, `BuildDecliningSearchConsolePagesAction`, `BuildPageSearchConsoleInsightsAction`, `BuildPageSeoReportAction`, `BuildRedirectOpportunityReportAction`, `BuildSchemaTemplateReportAction`, `BuildSeoAuditQueryAction`, `BuildSocialMetaAction`, `BuildTranslationCoverageQueryAction`, `CalculateSeoScoreAction`, `ClearAiDiscoveryCacheAction`, `CreateRedirectForBrokenLinkAction`, `FillAiDiscoveryPageSummaryAction`, `GenerateAiContentBriefAction`, `GenerateAiImageAction`, `GenerateAiLayoutAction`, `GenerateLlmsFullTxtAction`, `GenerateLlmsTxtAction`, `GeneratePageMarkdownAction`, `GeneratorPageContentAction`, `PageIsDiscoverableForAiDiscoveryAction`, `PageMetaSchemaAction`, `PersistAiDiscoverySnapshotAction`, `PersistPageSeoSnapshotAction`, `PersistSearchConsoleUrlMetricAction`, `RecordAiGenerationAction`, `RecordAiGenerationAction`, `RecordBrokenLinkAction`, `RefreshPageSeoSnapshotAction`, `RefreshSiteSeoSnapshotsAction`, `RenderContentMarkdownAction`, `ResolveAiDiscoveryPageProfileAction`, `ResolveAiDiscoveryProfileAction`, `SchemaGraphAction`, `SeedDefaultAiCrawlerRulesAction`, `SiteMetaSchemaAction`, `SubmitAiCreatorDraftAction`, `SuggestInternalLinksAction`, `SuggestMetaDescriptionsAction`, `SuggestPageTitlesAction`, `SyncAiDiscoveryPageProfilesAction`, `SyncSearchConsoleInsightsAction`, `UpdateAiDiscoveryPageInclusionAction`, `UpdateAiDiscoveryPageProfileAction`
- Public extension points: `AIOrchestratorSettings`, `ActionContract`, `AiActionContextInterface`, `ContentTargetContract`, `ExchangerInterface`, `RegisterSeoHeadHooks`, `SchemaTemplate`, `SearchConsoleClientInterface`, `SearchMetaDataSectionExtender`, `SearchMetaDataSectionExtenderResolverInterface`, `SeoPublishReportProvider`, `SeoSuiteSettings`

Code-only gaps detected:

- registries: 7/7 (`CapellCore::registerModels`, `PageSchemaExtender::TAG`, `RenderHookRegistry`, `RenderHookRegistry`, `registerPageSchemaExtenders`, and 2 more)
- config: 66/67 (`capell-seo-suite.CCBot`, `capell-seo-suite.ChatGPT-User`, `capell-seo-suite.Claude-SearchBot`, `capell-seo-suite.Claude-User`, `capell-seo-suite.ClaudeBot`, and 61 more)
- actions: 46/51 (`ApplyAiDraftAction`, `BaseAction`, `BreadcrumbsSchemaAction`, `BuildAiDiscoveryPageEntriesAction`, `BuildAiDiscoveryPageQueryAction`, and 41 more)
- extension: 1/12 (`RegisterSeoHeadHooks`)

</details>

<details>
<summary>Site Discovery (capell-app/site-discovery)</summary>

- Docs: README.md
- Commands: `capell:xml-sitemap {--site= : Only regenerate sitemaps for this site ID} {--incremental : Skip domains whose pages have not changed since the last run}`
- Service providers: `src/Providers/SiteDiscoveryServiceProvider.php`
- Registry calls: `CapellCore::registerModelInterceptor`
- Config keys: None found.
- Migrations: None found.
- Actions: `DiscoverPublicPagesAction`, `DiscoverPublicUrlsAction`, `GenerateSitemapAction`
- Public extension points: `DiscoverableUrlSource`, `Sitemapable`

Code-only gaps detected:

- registries: 1/1 (`CapellCore::registerModelInterceptor`)
- actions: 3/3 (`DiscoverPublicPagesAction`, `DiscoverPublicUrlsAction`, `GenerateSitemapAction`)

</details>

<details>
<summary>Tags (capell-app/tags)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: `capell:tags-install`
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/ConsoleServiceProvider.php`, `src/Providers/TagsServiceProvider.php`
- Registry calls: `CapellCore::registerModels`
- Config keys: None found.
- Migrations: `2026_05_10_190872_01_alter_tags_table.php`
- Actions: None found.
- Public extension points: None found.

Code-only gaps detected:

- registries: 1/1 (`CapellCore::registerModels`)

</details>

<details>
<summary>Theme Agency (capell-app/theme-agency)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: None found.
- Registry calls: None found.
- Config keys: None found.
- Migrations: None found.
- Actions: None found.
- Public extension points: None found.

</details>

<details>
<summary>Theme Corporate (capell-app/theme-corporate)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: None found.
- Registry calls: None found.
- Config keys: None found.
- Migrations: None found.
- Actions: None found.
- Public extension points: None found.

</details>

<details>
<summary>Theme Saas (capell-app/theme-saas)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: None found.
- Registry calls: None found.
- Config keys: None found.
- Migrations: None found.
- Actions: None found.
- Public extension points: None found.

</details>

<details>
<summary>Translation Manager (capell-app/translation-manager)</summary>

- Docs: README.md, docs/overview.md, docs/sources-stores-and-ai.md
- Commands: None found.
- Service providers: `src/Providers/AdminServiceProvider.php`, `src/Providers/TranslationManagerServiceProvider.php`
- Registry calls: `CapellAdmin::registerExtensionPage`
- Config keys: `capell-translation-manager.app_source`, `capell-translation-manager.key`, `capell-translation-manager.label`, `capell-translation-manager.locale_pattern`, `capell-translation-manager.package_paths`, `capell-translation-manager.package_source_writes`, `capell-translation-manager.path`, `capell-translation-manager.source_locale`, `capell-translation-manager.vendor_namespaces`, `capell-translation-manager.writable`
- Migrations: None found.
- Actions: `CreateLocaleFilesAction`, `DuplicateLocaleAction`, `ListInstalledLocalesAction`, `ListTranslationFilesAction`, `ListTranslationSourcesAction`, `LoadTranslationComparisonAction`, `SaveTranslationEntriesAction`, `TranslateSelectedEntriesAction`
- Public extension points: `TranslationAITranslator`, `TranslationFileStore`, `TranslationSourceResolver`

Code-only gaps detected:

- registries: 1/1 (`CapellAdmin::registerExtensionPage`)
- config: 10/10 (`capell-translation-manager.app_source`, `capell-translation-manager.key`, `capell-translation-manager.label`, `capell-translation-manager.locale_pattern`, `capell-translation-manager.package_paths`, and 5 more)
- actions: 8/8 (`CreateLocaleFilesAction`, `DuplicateLocaleAction`, `ListInstalledLocalesAction`, `ListTranslationFilesAction`, `ListTranslationSourcesAction`, and 3 more)

</details>

<details>
<summary>Welcome Tour (capell-app/welcome-tour)</summary>

- Docs: README.md, docs/overview.md, docs/steps-and-settings.md
- Commands: None found.
- Service providers: `src/Providers/WelcomeTourServiceProvider.php`
- Registry calls: `CapellAdmin::registerWelcomeTourStep`, `registerSettingsClass`
- Config keys: `capell-welcome-tour.description`, `capell-welcome-tour.element`, `capell-welcome-tour.enabled`, `capell-welcome-tour.icon`, `capell-welcome-tour.icon_color`, `capell-welcome-tour.key`, `capell-welcome-tour.sort`, `capell-welcome-tour.steps`, `capell-welcome-tour.title`, `capell-welcome-tour.visible`
- Migrations: None found.
- Actions: `CanShowWelcomeTourAction`, `SetUserWelcomeTourPreferenceAction`
- Public extension points: `WelcomeTourSettings`

Code-only gaps detected:

- registries: 2/2 (`CapellAdmin::registerWelcomeTourStep`, `registerSettingsClass`)
- config: 10/10 (`capell-welcome-tour.description`, `capell-welcome-tour.element`, `capell-welcome-tour.enabled`, `capell-welcome-tour.icon`, `capell-welcome-tour.icon_color`, and 5 more)
- actions: 2/2 (`CanShowWelcomeTourAction`, `SetUserWelcomeTourPreferenceAction`)

</details>

<details>
<summary>Wordpress Importer (capell-app/wordpress-importer)</summary>

- Docs: README.md, docs/credits-and-acknowledgements.md, docs/overview.md
- Commands: None found.
- Service providers: `src/Providers/WordPressImporterServiceProvider.php`
- Registry calls: None found.
- Config keys: None found.
- Migrations: None found.
- Actions: None found.
- Public extension points: None found.

</details>
