# Layout Builder

<!-- prettier-ignore-start -->

## What This Plugin Adds

Layout Builder is an **Available**, **Schema-owning** Capell package in the **Capell Foundation** product group. It ships as `capell-app/layout-builder` and extends these surfaces: admin, frontend, console.

Compose pages visually with reusable widgets and named layout areas - edit content fast in content-first mode, or drag-and-drop the full layout. Renders to clean, query-free public HTML that never leaks editor internals.

After install, admins get package-owned management surfaces and public users may see package-owned frontend output or routes.

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/layout-builder`
- Namespace: `Capell\LayoutBuilder`
- Theme key: not applicable

## Why It Matters

**For developers:** The package gives developers package-owned service providers, Actions, Data objects, models, Filament classes, and Blade views instead of pushing this behaviour into core or application code.

**For teams:** Compose pages visually with reusable widgets and named layout areas - edit content fast in content-first mode, or drag-and-drop the full layout. Renders to clean, query-free public HTML that never leaks editor internals.

## Screens And Workflow

Screenshot contract: `docs/screenshots.json`.

- Layout Builder visual editor with main and sidebar containers (admin, required).
- Layout Builder content-first editing mode (admin, required).
- Layout Builder add widget action (admin, required).
- Layout Builder add container action (admin, required).
- Layout Builder edit widget action (admin, required).
- Layout Builder edit container action (admin, required).
- Layout Builder responsive preview (admin, required).
- Layout Builder tree selection (admin, required).
- Layout Builder preset action fixture (frontend, required).
- Layout Builder undo and redo actions fixture (frontend, required).
- Layout Builder bulk change criteria fixture (frontend, required).
- Layout Builder bulk change review fixture (frontend, required).
- Layout Builder main and sidebar admin example (admin, required).
- Layout Builder main and sidebar public example (frontend, required).
- Layout Builder full-width public example (frontend, required).
- Widgets admin index (admin, required).
- Create and edit widget form with widget assets (admin, required).
- Sections admin index (admin, required).

## Technical Shape

- Service providers: `Capell\LayoutBuilder\LayoutBuilderServiceProvider`, `AbstractFoundationWidgetServiceProvider`.
- Config files: `packages/layout-builder/config/capell-layout-builder.php`.
- Migrations: `packages/layout-builder/database/migrations/2026_05_10_190841_01_create_layouts_table.php`, `packages/layout-builder/database/migrations/2026_05_10_190841_02_create_widgets_table.php`, `packages/layout-builder/database/migrations/2026_05_10_190841_03_create_widget_assets_table.php`, `packages/layout-builder/database/migrations/2026_05_10_190841_04_create_widget_widgets_table.php`, `packages/layout-builder/database/migrations/2026_05_10_190841_05_add_container_widgets_to_layouts_table.php`, `packages/layout-builder/database/migrations/2026_05_10_190841_06_create_layout_presets_table.php`, `packages/layout-builder/database/migrations/2026_06_07_000001_create_layout_bulk_change_tables.php`, `packages/layout-builder/database/migrations/2026_07_09_000001_create_public_widget_snapshots_table.php`, `packages/layout-builder/database/migrations/2026_07_10_000001_add_linked_preset_fields_to_layout_presets_table.php`, `packages/layout-builder/database/migrations/2026_07_10_000002_create_layout_preset_usages_table.php`, `packages/layout-builder/database/migrations/2026_07_10_000003_create_layout_preset_sync_runs_table.php`.
- Models: `Layout`, `LayoutBulkChangeResult`, `LayoutBulkChangeRun`, `LayoutPreset`, `LayoutPresetSyncResult`, `LayoutPresetSyncRun`, `LayoutPresetUsage`, `PublicWidgetSnapshot`, `Widget`, `WidgetAsset`, `WidgetWidget`.
- Filament classes: `CreateWidgetAction`, `ActionsRepeater`, `AlignSelect`, `AssetTypeSelect`, `AssetsRepeater`, `BackgroundSchema`, `BorderSelect`, `CarouselSettingsSchema`, `ColorSchemeComponent`, `ColumnInput`, `ContainerWidthSelect`, `CustomColorInput`, `and 83 more`.
- Livewire components: `AuthorizesLayoutBuilderAccess`, `HasLayoutActions`, `ManagesAssets`, `ManagesContainers`, `ManagesLayoutBuilderState`, `ManagesWidgets`, `LayoutBuilder`, `ModalTableSelect`, `LayoutBuilderActionFactory`.
- Policies: `LayoutPresetPolicy`.
- Listeners: `AfterRecordSaved`, `LayoutLoaded`, `MaintainPublicWidgetSnapshotsListener`, `SiteTreeRebuilt`, `TypeValidated`.
- Actions: `AddHeroWidgetToLayoutAction`, `AddWidgetToLayoutContainerAction`, `AnalyzeLayoutDiagnosticsAction`, `AnalyzeLayoutHealthAction`, `ApplyLayoutPresetAction`, `ApplyLayoutSidebarWidgetContributionsAction`, `ApplyStarterLayoutPresetAction`, `AttachWidgetToLayoutAreaAction`, `BuildLayoutBuilderTreeAction`, `BuildLayoutContentInventoryAction`, `BuildPublicLayoutGraphAction`, `BuildWidgetVisualRegressionManifestAction`, `and 69 more`.
- Data objects: `AdminLayoutPreviewData`, `AdminWidgetPreviewData`, `LayoutWidgetResourceUsageData`, `ActivityItemData`, `LayoutHealthData`, `LeastUsedWidgetData`, `RecentActivityData`, `UnusedWidgetData`, `WidgetGroupData`, `DemoSitePlanData`, `LayoutAssetBridgeData`, `LayoutBuilderStateData`, `and 38 more`.
- Jobs: `ApplyLayoutBulkChangeRunJob`, `SyncLinkedLayoutPresetJob`.
- Command signatures: `capell:layout-builder-install`, `capell:layout-builder:prune-bulk-change-runs`.
- Console command classes: `InstallCommand`, `LayoutBulkChangeCommand`, `PruneLayoutBulkChangeRunsCommand`, `PrunePublicWidgetSnapshotsCommand`, `ResyncLayoutPresetCommand`, `WidgetVisualRegressionCommand`.
- Manifest contributions: `admin-resource: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`, `asset: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`, `configurator: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`, `migration: Capell\LayoutBuilder\Manifest\LayoutBuilderMigrationsContribution`, `model: Capell\LayoutBuilder\Manifest\LayoutBuilderModelsContribution`, `page-type: Capell\LayoutBuilder\Manifest\LayoutBuilderPageTypesContribution`, `route: Capell\LayoutBuilder\Manifest\LayoutBuilderRoutesContribution`, `scheduled-job: Capell\LayoutBuilder\Manifest\LayoutBuilderBulkChangePruneScheduleContribution`, `scheduled-job: Capell\LayoutBuilder\Manifest\LayoutBuilderSnapshotPruneScheduleContribution`, `schema-extender: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`.
- Health checks: `Capell\LayoutBuilder\Health\LayoutBuilderHealthCheck`.
- Blade views: `packages/layout-builder/resources/views/components/filament/layout-builder/asset.blade.php`, `packages/layout-builder/resources/views/components/filament/layout-builder/assets.blade.php`, `packages/layout-builder/resources/views/components/filament/layout-builder/container.blade.php`, `packages/layout-builder/resources/views/components/filament/layout-builder/drag-handle-icon.blade.php`, `packages/layout-builder/resources/views/components/filament/layout-builder/widget.blade.php`, `packages/layout-builder/resources/views/components/infolists/entries/layout-widget.blade.php`, `packages/layout-builder/resources/views/components/infolists/entries/layout-widgets.blade.php`, `packages/layout-builder/resources/views/components/layout-widget-assets.blade.php`, `packages/layout-builder/resources/views/components/layout-widgets/content.blade.php`, `packages/layout-builder/resources/views/components/layout-widgets/extension-gated.blade.php`, `packages/layout-builder/resources/views/components/layout-widgets/extension-unavailable.blade.php`, `packages/layout-builder/resources/views/components/layout-widgets/image.blade.php`, `and 23 more`.
- Cache tags: `layout-builder`.

## Data Model

- Required tables: `layouts`, `widgets`, `widget_assets`, `widget_widgets`, `layout_presets`, `layout_bulk_change_runs`, `layout_bulk_change_results`, `layout_preset_usages`, `layout_preset_sync_runs`, `layout_preset_sync_results`, `public_widget_snapshots`.
- Models: `Layout`, `LayoutBulkChangeResult`, `LayoutBulkChangeRun`, `LayoutPreset`, `LayoutPresetSyncResult`, `LayoutPresetSyncRun`, `LayoutPresetUsage`, `PublicWidgetSnapshot`, `Widget`, `WidgetAsset`, `WidgetWidget`.
- Migration files: `2026_05_10_190841_01_create_layouts_table.php`, `2026_05_10_190841_02_create_widgets_table.php`, `2026_05_10_190841_03_create_widget_assets_table.php`, `2026_05_10_190841_04_create_widget_widgets_table.php`, `2026_05_10_190841_05_add_container_widgets_to_layouts_table.php`, `2026_05_10_190841_06_create_layout_presets_table.php`, `2026_06_07_000001_create_layout_bulk_change_tables.php`, `2026_07_09_000001_create_public_widget_snapshots_table.php`, `2026_07_10_000001_add_linked_preset_fields_to_layout_presets_table.php`, `2026_07_10_000002_create_layout_preset_usages_table.php`, `2026_07_10_000003_create_layout_preset_sync_runs_table.php`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: Docs gap unless the package has an explicit pruning command, retention setting, or tested cascade path.

## Install Impact

- Admin navigation: adds package-owned Filament classes when registered.
- Permissions: `ViewAny:Layout`, `View:Layout`, `Create:Layout`, `EditContent:Layout`, `EditLayout:Layout`, `Update:Layout`, `Delete:Layout`, `DeleteAny:Layout`, `Restore:Layout`, `ForceDelete:Layout`, `Replicate:Layout`, `Reorder:Layout`, `BulkMutate:Layout`.
- Public routes: none detected in package route files.
- Database changes: package migrations are declared.
- Settings: no package settings declared.
- Queues or schedules: review package jobs or schedules before install.
- Cache tags: `layout-builder`.
- Commands: `capell:layout-builder-install`, `capell:layout-builder:prune-bulk-change-runs`.

## Common Pitfalls

- Run migrations before opening package resources or public routes.
- Keep public Blade and cached HTML free of authoring markers, model IDs, permissions, signed editor URLs, and lazy database queries.
- Run package commands from the host app; in this repository use `vendor/bin/pest` for package tests.
- Keep `composer.json`, `composer.local.json`, `capell.json`, docs, screenshots, and tests aligned when the package surface changes.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Admin screen or command fails on missing table | Package migrations have not run | Check the tables listed in `Data Model` | Run host migrations and rerun the focused package test |
| Background work does not run | Queue worker or scheduled command is not active | Check package jobs, commands, and host scheduler configuration | Start the queue or scheduler, then run the focused command or package test |
| Public output leaks unexpected state | Render data, cache variation, or authoring boundary has regressed | Check public Blade, cache tags, and public-output safety tests | Move data loading out of Blade and rerun the package public-output tests |

## Quick Start

1. Install the package: `composer require capell-app/layout-builder`.
2. Run the required setup: `php artisan capell:layout-builder-install`.
3. Open the related Capell admin surface and verify Layout Builder appears.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Screenshot contract](docs/screenshots.json)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Related packages: [Block Library](../block-library/README.md), [Content Sections](../content-sections/README.md), [Frontend Authoring](../frontend-authoring/README.md), [Publishing Studio](../publishing-studio/README.md), [Structured Content Library](../structured-content-library/README.md).
- Focused tests: `vendor/bin/pest packages/layout-builder/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
