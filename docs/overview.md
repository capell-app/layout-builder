# Layout Builder

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend, console** · Product group: **Capell Foundation**

Layout Builder owns Capell's visual composition layer: reusable layouts, widgets, widget assets, content-first editing, public layout rendering, layout areas, presets, and widget visual-regression manifests.

It also gives admins a reviewed bulk change workflow for shared layout maintenance. Teams can define a typed operation, preview every affected layout and page, inspect before and after widget order, then approve the stored run only when the result is clear.

## Install

```bash
composer require capell-app/layout-builder
php artisan capell:layout-builder-install
```

The package requires `capell-app/admin`, `capell-app/block-library`, `capell-app/core`, and `capell-app/frontend` through Composer. The isolated audit harness confirmed that `block-library` is installed as a hard Composer dependency.

## Admin Surfaces

- `WidgetResource` for reusable widgets, widget metadata, widget assets, and layout relationships.
- `LayoutResource`, extending the core Layouts admin resource with package-specific table and editor behaviour.
- Page schema extenders for layout/content-first editing and hero editing.
- Layout schema extender for package layout fields.
- A "Bulk change layouts" Filament action wizard with criteria, preview review, warnings, skipped layouts, and approval.
- Livewire layout builder component and Filament assets.
- Dashboard widgets for layout health and recent activity when enabled by the host admin surface.

## Bulk Layout Change Review

Bulk changes are designed for layout maintenance that would otherwise become a fragile manual pass across many pages:

- move breadcrumbs below a hero widget;
- remove a deprecated widget from all active layouts;
- swap the order of two widgets;
- move a widget from a sidebar container into the main container.

The workflow is intentionally preview-first. `ResolveLayoutBulkChangeTargetsAction` finds candidate layouts. `PreviewLayoutBulkChangeAction` stores a run and per-layout result rows with original and proposed containers, hashes, page counts, structured diffs, warnings, and skipped reasons. `ApplyLayoutBulkChangeRunAction` only writes approved results whose current container hash still matches the preview.

That hash guard matters for real editorial teams. If someone edits a layout after the preview is generated, the apply step reports that result as drifted and leaves it alone instead of overwriting newer work.

Page-scoped `widget_assets` are migrated when a moved widget changes container or occurrence. Default widget assets are deliberately conservative in v1: previews warn or block when an occurrence change would make a default assignment ambiguous.

The command surface uses the same Actions as the Filament wizard:

```bash
php artisan capell:layouts:bulk-change --spec=/path/change.json --preview
php artisan capell:layouts:bulk-change --approve=run-uuid --json
php artisan capell:layouts:bulk-change --revert=run-uuid
```

Use the marketplace screenshot set in `docs/assets/marketplace/` when explaining this feature publicly: the criteria wizard, review table, approved run state, content-first editor, layout areas, and public render output are all declared in `capell.json`.

## Frontend Surfaces

- Public layout components under `resources/views/components/layout`.
- Main-content and named layout-area rendering through the resolved layout graph.
- Public widget payload resolution through `BuildPublicLayoutGraphAction`.
- Lazy public widget fragments through `GET /_fragments/{reference}` when a widget's presentation delivery mode is set to `lazy_fragment`.
- Interaction triggers on widget type defaults and layout widget instances. Interactions can open registered widget targets through the frontend lazy widget endpoint or open encrypted Layout Builder widget fragments through the fragment endpoint.

Public Blade must stay query-free and authoring-free. Rendered HTML should not expose editor state, signed URLs, field paths, admin labels, internal model identifiers, or package diagnostics.

Lazy fragments are encrypted-reference-only. The reference is decoded and revalidated against site, page, layout, language, container, widget key, and occurrence before rendering. Invalid, replayed, or unsafe references return a generic 404. The fragment route is reserved so it cannot fall through to the CMS page resolver, and fragment responses use their own cache headers instead of the page HTML cache.

Presentation controls are available on widget type defaults and per-layout widget instances. Basic controls cover preset, width, and alignment. Advanced delivery controls, including lazy fragments, loading strategy, device visibility, connection requirements, and custom width, require the `presentation.manage_advanced` permission.

Interaction controls are available beside presentation controls. Layout Builder previews show interaction chips such as `Play video -> widget` so editors can see that a widget has a public interactive target without exposing the encrypted public reference or internal widget metadata.

## Presentation And Delivery

Layout Builder uses the same presentation model as frontend widgets:

| Surface                | Storage                           |
| ---------------------- | --------------------------------- |
| Widget type default    | type `meta.presentation`          |
| Layout widget instance | layout widget `meta.presentation` |

Settings resolve in the same order as widgets: instance override, type default, preset default, system default. The system default is server-rendered output, so saved layouts keep their existing behaviour unless an editor or widget type explicitly changes delivery.

Set delivery mode to `lazy_fragment` when the widget should render as a public placeholder and fetch its widget HTML through `/_fragments/{reference}`. Keep above-the-fold widgets server-rendered unless there is a clear performance or UX reason to defer them.

## Interactions

Layout Builder supports interactions at two levels:

| Surface                | Storage                           |
| ---------------------- | --------------------------------- |
| Widget type default    | type `meta.interactions`          |
| Layout widget instance | layout widget `meta.interactions` |

Instance interactions replace type defaults when present. The shared admin schema lets editors choose a target type and behaviour, then configure the target widget or fragment settings in the same flow.

Fragment interactions are a first-class Layout Builder use case. When an interaction target is `fragment` and no `fragment_reference` is stored, public rendering generates an encrypted reference to the current widget. That lets a widget render a small trigger upfront and fetch its heavier widget HTML later through `/_fragments/{reference}`.

Use fragment interactions for optional or expensive widget content, video/detail panels, comparison sections, below-the-fold content, or page experiences where click/visibility should control when the widget renders.

Use widget interactions when the target is a reusable Capell widget, such as a video player, form, gallery, or calculator. The target widget renders through the frontend lazy widget endpoint, not through a Layout Builder fragment.

Public previews and page output must not expose widget keys, layout IDs, page IDs, model IDs, component names, package namespaces, field paths, or editor metadata. Preview chips are admin-only and show human labels such as `Play video -> widget`; public triggers use generic runtime data and encrypted target URLs.

## Visual Preview

The visual editor preview uses breakpoint-aware canvas width variables for desktop, tablet, and mobile frames. The preview tree compresses at narrower admin widths, and save controls stay in a sticky preview action bar so editors can save without losing their position in the canvas.

## Screenshot Plan

Current committed screenshots need recapture before marketplace promotion: the existing files render a broken default shell/oversized black arc instead of usable Capell UI. Keep them as runner evidence only until the capture app loads the correct package assets and seeded states.

- Widgets admin index.
- Create/edit widget form, including widget assets.
- Widget layouts relation manager.
- Layouts admin index with Layout Builder table extensions.
- Page form layout/content-first editor tab.
- Hero editor page extension.
- Public main content render.
- Public named layout area render.
- Layout health dashboard widget.
- Recent activity dashboard widget.

## Verification

- Package tests: `vendor/bin/pest packages/layout-builder/tests --configuration=phpunit.xml`.
- Harness install: `composer require capell-app/layout-builder:4.x-dev -W`, then `php artisan package:discover --ansi` and `php artisan migrate --graceful --ansi`.

## Known Risks

- Public render performance still needs an explicit query/time budget regression test for larger widget graphs.
- Content-first and layout-first editor screenshots should continue to be captured separately because they exercise different editor states.
- Layout health and recent activity dashboard widget screenshots should be added when the host admin dashboard enables those widgets.
