# Capell Packages Context

Capell Packages is the add-on package workspace for Capell CMS. It supplies optional modules for publishing, frontend rendering, editorial workflow, search, SEO, growth, operations, themes, assistant capabilities, and commercial integrations.

## Core Domain

**Capell CMS**
The host Laravel and Filament CMS. This repository does not own core, admin, or frontend foundations, but packages extend them through declared extension points.

**Package**
An installable Capell add-on under `packages/{package}`. A package owns its schema, providers, actions, data objects, models, translations, docs, tests, and runtime surfaces.

**Theme**
An installable frontend renderer package. A theme consumes content and theme runtime data; it should not own editorial content schema unless its package explicitly says so.

**Bundle**
A product grouping or metapackage that installs related packages together. A bundle is not necessarily a runtime module.

**Product Group**
The commercial or functional family a package belongs to, such as Foundation, Publishing Pro, Search & SEO, Growth, Operations, Theme Studio, or Commercial.

**Runtime Context**
The surface where package code runs: Admin, Frontend, Console, or Shared.

## Publishing Domain

**Site**
A Capell site. Many packages scope records, pages, analytics, navigation, and rendering behaviour to a site.

**Language**
A content language. Packages that create pages, widgets, articles, countries, or navigation often create translations for each language.

**Page**
A core content record rendered on the frontend and edited in the admin. Packages may create page types, extend page schemas, attach metadata, or react to page lifecycle events.

**Page Type**
A typed definition of page behaviour. Packages register page types to control frontend components, admin configuration, URL parameters, accessibility, listing behaviour, and required fields.

**Layout**
A structural rendering definition used by page and widget systems. Layouts belong to the publishing surface and should be registered through Capell extension points.

**Widget**
A reusable Mosaic rendering unit with type, translations, metadata, and optional assets. Widgets are content building blocks, not general-purpose service objects.

**Section**
A Mosaic content region or reusable content block that can be related to pages, widgets, assets, and translations.

**Article**
A Blog publishing record. Articles reuse Capell site, layout, type, translation, URL, tag, media, and Mosaic widget behaviour while keeping article-specific workflow in the Blog package.

**Tag**
A taxonomy record used to classify articles and other taggable content.

**Navigation**
A site or global menu structure. Navigation packages should own navigation item resolution and menu setup rather than scattering menu writes through callers.

**Redirect**
A URL forwarding rule. Redirect packages own automatic redirect recording, redirect validation, and frontend redirect resolution.

## Editorial Workflow Domain

**Workspace**
A draft publishing workspace for editing, review, preview, scheduling, and publishing changes separately from live content.

**Draftable**
A model that can participate in workspace draft/publish flows. Draftable models must implement the draftable contract and be registered with the workspace system.

**Version**
A recorded content version used for publishing history and rollback.

**Preview Link**
A signed, scoped link that lets a user view draft or staged content without publishing it.

**Approval**
A review decision in the workspace workflow, such as approve, reject, request changes, or submit for approval.

**Publish Check**
A validation step that can block or warn before content is published.

## Growth And Insight Domain

**Analytics Visit**
A first-party visit tracked by the Analytics package, scoped to consent, site, language, visitor identity, and landing context.

**Analytics Event**
A tracked event such as page view, click, or custom action.

**Consent**
A visitor decision that governs which analytics categories may be recorded.

**Campaign**
A marketing initiative with landing pages, CTA blocks, UTM attribution, conversion goals, and reporting.

**Conversion**
A recorded campaign outcome such as a page view, CTA click, or form submission.

**Form**
A frontend submission surface that owns fields, validation, and submissions.

**Submission**
A form response with status and moderation workflow.

## Search, SEO, And Theme Domain

**SEO Snapshot**
A stored or computed view of page SEO state, including metadata, structured data, broken links, scores, and reporting inputs.

**Sitemap**
A discoverable index of frontend URLs. Sitemap generation may be extended by packages that own frontend content types.

**Search Query**
A visitor search phrase and its result/click analytics.

**Theme Runtime**
The resolved data needed to render a page through Theme Studio: selected theme, brand profile, tokens, page adapter data, preview context, and renderer output.

**Theme Draft**
A staged theme change awaiting preview, approval, or publishing.

**Renderer**
A theme package implementation that turns Theme Runtime data into frontend output.

## Operations And Integration Domain

**Authentication Log**
An audit record for login, logout, failed login, and activity metadata.

**Import Session**
A tracked import workflow for package archives, WordPress data, or recovery operations.

**Package Manifest**
A structured description of exported package data, dependencies, ownership, and relation resolution needs.

**Health Check**
An operational report item for setup, cache, migrations, queue, registry, package installation, config drift, or build status.

**Assistant Module**
A registered provider of assistant capabilities. Assistant modules expose capabilities through data objects and actions, not through inline UI logic.

**Capability**
An assistant or MCP action that can be listed, previewed, approved, or executed.

**Deployment Connection**
A configured link to a Git provider used for publishing composer/package changes through pull requests.

## Architectural Terms In This Context

**Package Surface**
The set of Admin, Frontend, Console, and Shared entry points a package exposes.

**Package Interior**
The implementation details inside a package: private helpers, support classes, internal loaders, internal creators, and implementation-specific services.

**Extension Point**
A declared way for packages to collaborate without reaching into another package's interior. Examples include Capell registries, schema extenders, render hooks, settings registries, contracts, events, and action calls.

**Registry**
A module that collects package-provided definitions such as assistant modules, layout presets, schema templates, sitemap page types, theme renderers, or workspace draftables.

**Creator**
A module that creates demo, setup, seed, layout, widget, navigation, page, or package data.

**Loader**
A module that reads and prepares domain data for frontend rendering or setup workflows.

**Configurator**
A module that contributes Filament schema, table, widget, or page configuration.

**Render Hook**
A frontend or admin insertion point where a package can register output without editing the host view in place.

## Cross-Package Rules

- Package code should prefer Actions for domain writes and workflow orchestration.
- Data objects should cross package seams instead of loose arrays when structure matters.
- Filament resources, Livewire components, controllers, commands, observers, and listeners should delegate domain behaviour to Actions.
- Support modules may exist, but if a Support module owns workflow, persistence, or cross-package coordination, it should be considered part of the domain and reviewed for a deeper interface.
- Package interiors should not be used as informal extension points. If another package needs behaviour, create or reuse a declared extension point.
- One-off adapters are suspect unless variation is imminent or the interface already provides meaningful locality.
- Tests should target package interfaces with enough leverage to verify behaviour without knowing every implementation detail.
