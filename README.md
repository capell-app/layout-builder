# Layout Builder

<!-- prettier-ignore-start -->

## What This Plugin Adds

Layout Builder is an **Available**, **Schema-owning** Capell package in the **Capell Foundation** product group. It ships as `capell-app/layout-builder` and extends these surfaces: admin, frontend, console.

Layout Builder adds a visual page composition workflow with layout areas, widgets, reusable widgets, presets, and reversible layout edits.

Editors can add, reorder, resize, and edit page widgets in admin. Visitors receive the saved layout graph as ordinary public output without authoring markers.

Evidence: [`src/LayoutBuilderServiceProvider.php`](src/LayoutBuilderServiceProvider.php), [`src/Actions/PersistLayoutBuilderStateAction.php`](src/Actions/PersistLayoutBuilderStateAction.php), [`src/Actions/SaveLayoutPresetAction.php`](src/Actions/SaveLayoutPresetAction.php), [`tests/Feature/Livewire/LayoutBuilderContentFirstTest.php`](tests/Feature/Livewire/LayoutBuilderContentFirstTest.php), [`src/Actions/BuildPublicLayoutGraphAction.php`](src/Actions/BuildPublicLayoutGraphAction.php), [`tests/Feature/Render/LayoutBuilderPublicRenderingSafetyTest.php`](tests/Feature/Render/LayoutBuilderPublicRenderingSafetyTest.php), [`tests/Feature/Livewire/LayoutPresetLivewireTest.php`](tests/Feature/Livewire/LayoutPresetLivewireTest.php).

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/layout-builder`
- Namespace: `Capell\LayoutBuilder`
- Theme key: not applicable

## Why It Matters

**For developers:** Registries and typed Actions provide stable extension points for widget definitions, layout widgets, presets, and public render data.

**For teams:** Choose Layout Builder when page composition changes; use Content Sections for reusable published sections or Structured Content Library for typed records such as testimonials and FAQs. Both can feed a Layout Builder composition. If a site only needs a few fixed templates, its existing Laravel templates may be enough.

Evidence: [`src/Support/LayoutWidgets/LayoutWidgetRegistry.php`](src/Support/LayoutWidgets/LayoutWidgetRegistry.php), [`src/Support/WidgetExtensions/WidgetExtensionRegistry.php`](src/Support/WidgetExtensions/WidgetExtensionRegistry.php), [`src/Actions/BuildPublicLayoutGraphAction.php`](src/Actions/BuildPublicLayoutGraphAction.php), [`tests/Integration/PublicLayoutGraphActionTest.php`](tests/Integration/PublicLayoutGraphActionTest.php), [`docs/overview.admin.md`](docs/overview.admin.md), [`src/Actions/CreateLinkedLayoutPresetAction.php`](src/Actions/CreateLinkedLayoutPresetAction.php), [`src/Actions/InsertLinkedLayoutPresetAction.php`](src/Actions/InsertLinkedLayoutPresetAction.php).

## Screens And Workflow

Screenshot contract: `docs/screenshots.json`.

## Content Composition

![Conceptual article composition from Core page content and blueprint through Layout Builder areas, widgets, assets, and theme-rendered public HTML](docs/images/content-composition.svg)

_The Page and its blueprint remain Core inputs. Layout Builder owns the areas, widget keys, widget definitions, and widget assets that `BuildPublicLayoutGraphAction` turns into a public layout graph; Frontend owns the final public render contract._

[Canonical Mermaid source](docs/images/content-composition.mmd)

What changes when you edit an article, its layout, or its theme? In this illustrative example, **A day on the coast** uses `main` for an article content widget and `sidebar` for a related-reading widget; these are example choices, not seeded defaults.

- **Content edit:** revise the article title or body in its blueprint-defined content; the example keeps its `main` and `sidebar` placements.
- **Layout edit:** move or reorder widget placements in the selected layout; this changes composition and may affect other pages sharing that layout.
- **Theme change:** change templates, typography, spacing and area presentation. Check the theme's available areas and widget support; switching themes does not guarantee an identical composition.

Source: [`BuildPublicLayoutGraphAction`](src/Actions/BuildPublicLayoutGraphAction.php) maps layout containers and ordered widgets into public data; [`LayoutLoader`](src/Support/Loader/LayoutLoader.php) resolves widgets and their assets. Page, Blueprint, Translation, Layout and Theme remain Core models; Widget and WidgetAsset belong to optional Layout Builder.

![Layout Builder visual editor with main and sidebar containers](docs/screenshots/layout-builder-editor-main-sidebar.png)

![Layout Builder active-theme container settings](docs/screenshots/layout-builder-edit-container-theme-settings.png)

- Layout Builder visual editor with main and sidebar containers (admin, required evidence).
- Layout Builder content-first editing mode (admin, supplementary evidence).
- Layout Builder add widget action (admin, supplementary evidence).
- Layout Builder add container action (admin, supplementary evidence).
- Layout Builder edit widget action (admin, supplementary evidence).
- Layout Builder edit container action (admin, supplementary evidence).
- Layout Builder active-theme container settings (admin, required evidence).
- Layout Builder responsive padding controls on mobile (admin, required evidence).
- Layout Builder active-theme container settings on mobile (admin, required evidence).
- Layout Builder responsive preview (admin, supplementary evidence).
- Layout Builder tree selection (admin, supplementary evidence).
- Layout Builder filtered tree keyboard navigation (admin, supplementary evidence).
- Layout Builder preset action fixture (frontend, supplementary evidence).
- Layout Builder undo and redo actions fixture (frontend, supplementary evidence).
- Layout Builder bulk change criteria fixture (frontend, supplementary evidence).
- Layout Builder bulk change review fixture (frontend, supplementary evidence).
- Layout Builder main and sidebar admin example (admin, supplementary evidence).
- Layout Builder main and sidebar public example (frontend, supplementary evidence).
- Layout Builder full-width public example (frontend, supplementary evidence).
- Widgets admin index (admin, required evidence).
- Create and edit widget form with widget assets (admin, required evidence).
- Unused and disabled widget integrity state (admin, supplementary evidence).
- Broken and unscoped widget asset integrity states (admin, supplementary evidence).
- Sections admin index (admin, required evidence).
- Widgets admin index with admin sidebar menu open (admin, supplementary evidence).

## Works With

- [Content Sections](../content-sections/README.md): optional integration backed by the interop evidence map.
- [Frontend Authoring](../frontend-authoring/README.md): optional integration backed by the interop evidence map.
- [Publishing Studio](../publishing-studio/README.md): optional integration backed by the interop evidence map.
- [Structured Content Library](../structured-content-library/README.md): optional integration backed by the interop evidence map.

## Technical Shape

### Service providers

- `Capell\LayoutBuilder\LayoutBuilderServiceProvider`
- `AbstractFoundationWidgetServiceProvider`

### Config files

- `packages/layout-builder/config/capell-layout-builder.php`

### Migrations

- `packages/layout-builder/database/migrations/2026_05_10_190841_02_create_widgets_table.php`
- `packages/layout-builder/database/migrations/2026_05_10_190841_03_create_widget_assets_table.php`
- `packages/layout-builder/database/migrations/2026_05_10_190841_04_create_widget_widgets_table.php`
- `packages/layout-builder/database/migrations/2026_05_10_190841_05_add_container_widgets_to_layouts_table.php`
- `packages/layout-builder/database/migrations/2026_05_10_190841_06_create_layout_presets_table.php`
- `packages/layout-builder/database/migrations/2026_06_07_000001_create_layout_bulk_change_tables.php`
- `packages/layout-builder/database/migrations/2026_07_09_000001_create_public_widget_snapshots_table.php`
- `packages/layout-builder/database/migrations/2026_07_10_000001_add_linked_preset_fields_to_layout_presets_table.php`
- `packages/layout-builder/database/migrations/2026_07_10_000002_create_layout_preset_usages_table.php`
- `packages/layout-builder/database/migrations/2026_07_10_000003_create_layout_preset_sync_runs_table.php`
- `packages/layout-builder/database/migrations/2026_08_28_000003_change_widget_visibility_to_datetime.php`
- `packages/layout-builder/database/migrations/2026_09_01_000001_add_shadowed_by_workspace_id_to_widgets_table.php`

### Models

- `Layout`
- `LayoutBulkChangeResult`
- `LayoutBulkChangeRun`
- `LayoutPreset`
- `LayoutPresetSyncResult`
- `LayoutPresetSyncRun`
- `LayoutPresetUsage`
- `PublicWidgetSnapshot`
- `Widget`
- `WidgetAsset`
- `WidgetWidget`

### Filament classes

- `CreateWidgetAction`
- `ActionsRepeater`
- `AlignSelect`
- `AssetTypeSelect`
- `AssetsRepeater`
- `BackgroundSchema`
- `BorderSelect`
- `CarouselSettingsSchema`
- `ColorSchemeComponent`
- `ColumnInput`
- `ContainerWidthSelect`
- `CustomColorInput`
- `HeadingSizeSelect`
- `HeadingStyleSelect`
- `HtmlClassInput`
- `LayoutTab`
- `MarginSelect`
- `PaddingSelect`
- `HeroEditor`
- `LayoutTab`
- `PageModelSelect`
- `ResponsiveLayoutPatternSchema`
- `ResponsiveLayoutPatternSelect`
- `SizeSelect`
- `SpacingSelect`
- `TagSelect`
- `AdminSchema`
- `ComponentSection`
- `CreateDetailsSchema`
- `DisplaySection`
- `ResultsOverrideSchema`
- `ResultsSchema`
- `SettingsSchema`
- `WidgetAdminTab`
- `WidgetDisplayTab`
- `WidgetPresentationTabs`
- `WidgetSettingsTab`
- `TranslationsRepeater`
- `TypeSelect`
- `WidgetSelect`
- `DefaultLayoutContainerConfigurator`
- `DefaultLayoutWidgetConfigurator`
- `PageLayoutWidgetConfigurator`
- `ResultsLayoutWidgetConfigurator`
- `WidgetTypeConfigurator`
- `AbstractWidgetAssetConfigurator`
- `AssetsWidgetConfigurator`
- `CTASectionWidgetConfigurator`
- `CardGridWidgetConfigurator`
- `CarouselWidgetConfigurator`
- `DefaultWidgetConfigurator`
- `FeatureListWidgetConfigurator`
- `HeroBannerWidgetConfigurator`
- `HeroWidgetConfigurator`
- `ImageGalleryWidgetConfigurator`
- `KitchenSinkReferenceWidgetConfigurator`
- `ModernAlternatingContentConfigurator`
- `ModernCTASectionConfigurator`
- `ModernCardGridConfigurator`
- `ModernFaqConfigurator`
- `ModernFeatureListConfigurator`
- `ModernHeroBannerConfigurator`
- `ModernImageGalleryConfigurator`
- `ModernPricingTableConfigurator`
- `ModernProcessStepsConfigurator`
- `ModernStatsSectionConfigurator`
- `ModernTeamMembersConfigurator`
- `ModernTestimonialsConfigurator`
- `NavigationWidgetConfigurator`
- `PageContentWidgetConfigurator`
- `PageWidgetAssetForm`
- `RegisteredAssetWidgetAssetForm`
- `ResultsWidgetConfigurator`
- `SystemWidgetConfigurator`
- `HeroPageSchemaExtender`
- `LayoutBuilderResource`
- `LayoutPresetResource`
- `ListLayoutPresets`
- `LayoutResource`
- `CreateLayout`
- `EditLayout`
- `ListLayouts`
- `LayoutSchemaExtender`
- `LayoutsTable`
- `PageSchemaExtender`
- `PageSelectionTable`
- `CreateWidget`
- `EditWidget`
- `ListWidgets`
- `LayoutsRelationManager`
- `WidgetAssetsRelationManager`
- `WidgetAssetForm`
- `WidgetForm`
- `WidgetAssetsTable`
- `WidgetSelectionTable`
- `WidgetsTable`
- `WidgetResource`
- `LayoutHealthFilamentWidget`
- `RecentActivityFilamentWidget`

### Livewire components

- `AuthorizesLayoutBuilderAccess`
- `HasLayoutActions`
- `ManagesAssets`
- `ManagesContainers`
- `ManagesLayoutBuilderState`
- `ManagesWidgets`
- `LayoutBuilder`
- `ModalTableSelect`
- `LayoutBuilderActionFactory`

### Policies

- `LayoutPresetPolicy`

### Extension contracts

- `PublicLayoutWidgetAssetsRenderer`
- `LayoutContainerSchemaExtender`
- `WidgetAssetSchemaExtender`
- `WidgetSchemaExtender`
- `LayoutContainerThemePresentationProjector`
- `LayoutContentGroupContributor`
- `LayoutSidebarWidgetContributor`
- `PublicLayoutWidgetPayloadContributor`
- `PublicLayoutWidgetPayloadResolver`
- `WidgetAssetReferenceRepointer`
- `WidgetExtensionBatchPayloadResolver`
- `WidgetExtensionDependencyResolver`
- `WidgetExtensionStateUpcaster`
- `WidgetSnapshotLocatorCipher`

### Listeners

- `AfterRecordSaved`
- `LayoutLoaded`
- `MaintainPublicWidgetSnapshotsListener`
- `SiteTreeRebuilt`
- `TypeValidated`

### Actions

- `AddHeroWidgetToLayoutAction`
- `AddWidgetToLayoutContainerAction`
- `AnalyzeLayoutDiagnosticsAction`
- `AnalyzeLayoutHealthAction`
- `ApplyLayoutPresetAction`
- `ApplyLayoutSidebarWidgetContributionsAction`
- `ApplyStarterLayoutPresetAction`
- `AttachWidgetToLayoutAreaAction`
- `BuildLayoutBuilderTreeAction`
- `BuildLayoutContentInventoryAction`
- `BuildLayoutHealthWorkQueueAction`
- `BuildPublicLayoutGraphAction`
- `BuildWidgetDeletionImpactAction`
- `BuildWidgetVisualRegressionManifestAction`
- `ApplyLayoutBulkChangeRunAction`
- `ApplyLayoutWidgetOperationToContainersAction`
- `PreviewLayoutBulkChangeAction`
- `QueueLayoutBulkChangeRunAction`
- `ResolveLayoutBulkChangeTargetsAction`
- `RevertLayoutBulkChangeRunAction`
- `ScopeLayoutBulkChangeQueryForActorAction`
- `CountLinkedLayoutPresetUsagesAction`
- `CreateHeroWidgetAction`
- `CreateLayoutBuilderDemoSiteAction`
- `CreateLayoutPresetSyncRunAction`
- `CreateLinkedLayoutPresetAction`
- `FindReusableWidgetsAction`
- `BuildLayoutBuilderFragmentReferenceAction`
- `RenderPublicFragmentAction`
- `ResolveLayoutBuilderFragmentWidgetVersionAction`
- `GenerateLayoutPreviewImageAction`
- `GetLayoutPreviewImageUrlAction`
- `GetWidgetContainerWidthAction`
- `HeroWidgetHasPrimaryHeadingAction`
- `InsertLinkedLayoutPresetAction`
- `InstallLayoutBuilderPackageAction`
- `InstallLayoutBuilderWidgetCatalogAction`
- `InstallPackageAction`
- `InvalidateLayoutPreviewImageAction`
- `InvalidateTypeLayoutPreviewImagesAction`
- `InvalidateWidgetLayoutPreviewImagesAction`
- `BuildLayoutWidgetResourceUsagesAction`
- `RenderLazyLayoutWidgetAction`
- `LinkLayoutPresetContainerAction`
- `ListLayoutPresetsAction`
- `MakeWidgetAction`
- `CreateLayoutFragmentAction`
- `NormalizeLayoutBuilderStateAction`
- `PasteLayoutFragmentAction`
- `PushLayoutMutationSnapshotAction`
- `RedoLayoutMutationSnapshotAction`
- `ReorderLayoutContainerAction`
- `ReorderLayoutWidgetAction`
- `ResizeLayoutContainerAction`
- `UndoLayoutMutationSnapshotAction`
- `NormalizeLayoutContainerPaddingAction`
- `PersistLayoutBuilderStateAction`
- `PreviewLayoutPlanAction`
- `PruneLayoutBulkChangeRunsAction`
- `PublishLayoutBuilderAdminAssetsAction`
- `RenderAdminLayoutPreviewAction`
- `RepointWidgetAssetReferencesAction`
- `ResetLayoutContainerThemeSettingsAction`
- `ResolveAdminWidgetPreviewDataAction`
- `ResolveLayoutAreaContainersAction`
- `ResolveLayoutContainerPresentationAction`
- `ResolvePublicWidgetAssetsAction`
- `ResolvePublicWidgetRenderContextAction`
- `ResolveWidgetPresentationDataAction`
- `RunLinkedLayoutPresetSyncAction`
- `SaveFormComponentRelationshipAction`
- `SaveLayoutPresetAction`
- `SeedWidgetIntegrityScreenshotFixturesAction`
- `SetupLayoutBuilderPackageAction`
- `StripLayoutPresetLinkAction`
- `SummarizeLayoutChangesAction`
- `SyncLayoutPresetUsagesAction`
- `UpdateLinkedLayoutPresetItemAction`
- `WidgetContractValidatorAction`
- `BuildPublicWidgetPayloadsAction`
- `RenderPublicWidgetExtensionAction`
- `ResolveWidgetExtensionDependenciesAction`
- `RestoreWidgetInteractionContextAction`
- `WidgetIsSlotAction`
- `BuildPublicWidgetInteractionLocatorsAction`
- `PrunePublicWidgetSnapshotsAction`
- `RebuildPublicWidgetSnapshotsAction`
- `ResolvePublicWidgetSnapshotAction`
- `RevokePublicWidgetSnapshotsAction`
- `WithdrawPublicWidgetSnapshotsAction`

### Data objects

- `AdminLayoutPreviewData`
- `AdminWidgetPreviewData`
- `ActivityItemData`
- `LayoutHealthData`
- `LayoutHealthWorkQueueItemData`
- `LeastUsedWidgetData`
- `RecentActivityData`
- `UnusedWidgetData`
- `WidgetGroupData`
- `DemoSitePlanData`
- `LayoutAssetBridgeData`
- `LayoutBuilderStateData`
- `LayoutBuilderTreeContainerData`
- `LayoutBuilderTreeData`
- `LayoutBuilderTreeWidgetData`
- `LayoutBulkChangeCriteriaData`
- `LayoutBulkWidgetOperationData`
- `LayoutBulkWidgetOperationResultData`
- `LayoutChangeData`
- `LayoutContainerPresentationData`
- `LayoutContainerResponsivePaddingData`
- `LayoutContainerSchemaContextData`
- `LayoutContainerThemePresentationData`
- `LayoutContentGroupData`
- `LayoutContentInventoryContextData`
- `LayoutContentInventoryData`
- `LayoutContentItemData`
- `LayoutDiagnosticData`
- `LayoutFragmentData`
- `LayoutMutationHistoryData`
- `LayoutMutationNavigationData`
- `LayoutMutationResultData`
- `LayoutPlanData`
- `LayoutPlanResultData`
- `LayoutPresetData`
- `LayoutPresetLinkData`
- `LayoutSidebarWidgetData`
- `LayoutWidgetCatalogDefinitionData`
- `LayoutWidgetDefinitionData`
- `PublicFragmentRenderResultData`
- `PublicLayoutContainerData`
- `PublicLayoutGraphData`
- `PublicLayoutWidgetData`
- `PublicWidgetRenderContextData`
- `DiscoveredWidgetExtensionData`
- `WidgetExtensionCapabilitiesData`
- `WidgetExtensionCollisionData`
- `WidgetExtensionDefinitionData`
- `WidgetExtensionDependencyData`
- `WidgetExtensionPayloadBatchData`
- `WidgetExtensionPayloadInputData`
- `WidgetExtensionRenderContextData`
- `WidgetLayoutUsageData`
- `WidgetScaffoldData`
- `WidgetSnapshotLocatorData`

### Jobs

- `ApplyLayoutBulkChangeRunJob`
- `SyncLinkedLayoutPresetJob`

### Command signatures

- `capell:layout-builder-install`
- `capell:layout-builder:prune-bulk-change-runs`

### Manifest action API

- `install: Capell\LayoutBuilder\Actions\InstallLayoutBuilderPackageAction`
- `pruneLayoutBulkChangeRuns: Capell\LayoutBuilder\Actions\PruneLayoutBulkChangeRunsAction`
- `setup: Capell\LayoutBuilder\Actions\SetupLayoutBuilderPackageAction`

### Scheduled commands

- `capell:layout-builder:prune-bulk-change-runs (daily; manifest declared)`
- `capell:widget-snapshots:prune (daily; manifest declared)`

### Console command classes

- `InstallCommand`
- `LayoutBulkChangeCommand`
- `PruneLayoutBulkChangeRunsCommand`
- `PrunePublicWidgetSnapshotsCommand`
- `ResyncLayoutPresetCommand`
- `WidgetVisualRegressionCommand`

### Manifest contributions

- `admin-resource: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`
- `asset: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`
- `configurator: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`
- `migration: Capell\LayoutBuilder\Manifest\LayoutBuilderMigrationsContribution`
- `model: Capell\LayoutBuilder\Manifest\LayoutBuilderModelsContribution`
- `page-type: Capell\LayoutBuilder\Manifest\LayoutBuilderPageTypesContribution`
- `route: Capell\LayoutBuilder\Manifest\LayoutBuilderRoutesContribution`
- `scheduled-job: Capell\LayoutBuilder\Manifest\LayoutBuilderBulkChangePruneScheduleContribution`
- `scheduled-job: Capell\LayoutBuilder\Manifest\LayoutBuilderSnapshotPruneScheduleContribution`
- `schema-extender: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`

### Health checks

- `Capell\LayoutBuilder\Health\LayoutBuilderHealthCheck`

### Blade views

- `packages/layout-builder/resources/views/components/filament/layout-builder/asset.blade.php`
- `packages/layout-builder/resources/views/components/filament/layout-builder/assets.blade.php`
- `packages/layout-builder/resources/views/components/filament/layout-builder/container.blade.php`
- `packages/layout-builder/resources/views/components/filament/layout-builder/drag-handle-icon.blade.php`
- `packages/layout-builder/resources/views/components/filament/layout-builder/widget.blade.php`
- `packages/layout-builder/resources/views/components/infolists/entries/layout-widget.blade.php`
- `packages/layout-builder/resources/views/components/infolists/entries/layout-widgets.blade.php`
- `packages/layout-builder/resources/views/components/layout-widget-assets.blade.php`
- `packages/layout-builder/resources/views/components/layout-widgets/content.blade.php`
- `packages/layout-builder/resources/views/components/layout-widgets/extension-gated.blade.php`
- `packages/layout-builder/resources/views/components/layout-widgets/extension-unavailable.blade.php`
- `packages/layout-builder/resources/views/components/layout-widgets/image.blade.php`
- `packages/layout-builder/resources/views/components/layout-widgets/index.blade.php`
- `packages/layout-builder/resources/views/components/layout-widgets/interaction-target.blade.php`
- `packages/layout-builder/resources/views/components/layout-widgets/runtime-wrapper.blade.php`
- `packages/layout-builder/resources/views/components/layout-widgets/title.blade.php`
- `packages/layout-builder/resources/views/components/layout/area.blade.php`
- `packages/layout-builder/resources/views/components/layout/container.blade.php`
- `packages/layout-builder/resources/views/components/layout/main-content.blade.php`
- `packages/layout-builder/resources/views/components/layout/widget.blade.php`
- `packages/layout-builder/resources/views/filament/actions/layout-bulk-change-review.blade.php`
- `packages/layout-builder/resources/views/filament/layout-builder/admin-preview/container.blade.php`
- `packages/layout-builder/resources/views/filament/layout-builder/admin-preview/page.blade.php`
- `packages/layout-builder/resources/views/filament/layout-builder/admin-preview/widget-fallback.blade.php`
- `packages/layout-builder/resources/views/filament/layout-builder/previews/default.blade.php`
- `packages/layout-builder/resources/views/filament/layout-builder/previews/page-content.blade.php`
- `packages/layout-builder/resources/views/filament/resources/widgets/widget-card.blade.php`
- `packages/layout-builder/resources/views/filament/widgets/layout-health.blade.php`
- `packages/layout-builder/resources/views/filament/widgets/recent-activity.blade.php`
- `packages/layout-builder/resources/views/frontend-authoring/filament-shell-assets.blade.php`
- `packages/layout-builder/resources/views/frontend-authoring/layout-builder-editor.blade.php`
- `packages/layout-builder/resources/views/livewire/filament/layout-builder/content-first.blade.php`
- `packages/layout-builder/resources/views/livewire/filament/layout-builder/index.blade.php`
- `packages/layout-builder/resources/views/livewire/filament/layout-builder/visual-editor-markup.blade.php`
- `packages/layout-builder/resources/views/livewire/filament/layout-builder/visual-editor.blade.php`
- `packages/layout-builder/resources/views/livewire/filament/layout-builder/visual-tree.blade.php`
- `packages/layout-builder/resources/views/livewire/filament/layout-builder/widgets-table-select.blade.php`

### Cache tags

- `layout-builder`


## Data Model

- Required tables: `layouts`, `widgets`, `widget_assets`, `widget_widgets`, `layout_presets`, `layout_bulk_change_runs`, `layout_bulk_change_results`, `layout_preset_usages`, `layout_preset_sync_runs`, `layout_preset_sync_results`, `public_widget_snapshots`.
- Models: `Layout`, `LayoutBulkChangeResult`, `LayoutBulkChangeRun`, `LayoutPreset`, `LayoutPresetSyncResult`, `LayoutPresetSyncRun`, `LayoutPresetUsage`, `PublicWidgetSnapshot`, `Widget`, `WidgetAsset`, `WidgetWidget`.
- Core record references in migrations: `sites via site_id`, `languages via language_id`, `layouts via layout_id`, `widgets via widget_id`, `themes via theme_id`.
- Migration files: `2026_05_10_190841_02_create_widgets_table.php`, `2026_05_10_190841_03_create_widget_assets_table.php`, `2026_05_10_190841_04_create_widget_widgets_table.php`, `2026_05_10_190841_05_add_container_widgets_to_layouts_table.php`, `2026_05_10_190841_06_create_layout_presets_table.php`, `2026_06_07_000001_create_layout_bulk_change_tables.php`, `2026_07_09_000001_create_public_widget_snapshots_table.php`, `2026_07_10_000001_add_linked_preset_fields_to_layout_presets_table.php`, `2026_07_10_000002_create_layout_preset_usages_table.php`, `2026_07_10_000003_create_layout_preset_sync_runs_table.php`, `2026_08_28_000003_change_widget_visibility_to_datetime.php`, `2026_09_01_000001_add_shadowed_by_workspace_id_to_widgets_table.php`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: migrations declare cascade-on-delete relationships and null-on-delete relationships; retention is scheduled through `capell:widget-snapshots:prune` (daily; declared in the manifest) and `capell:layout-builder:prune-bulk-change-runs` (daily; declared in the manifest).

## Install Impact

- Required packages: `capell-app/core`, `capell-app/admin`, `capell-app/block-library`, `capell-app/frontend`.
- Admin navigation: declares `admin-resource: LayoutBuilderAdminRegistrar`; each Filament page or resource controls its own navigation visibility.
- Admin/editor extensions: `configurator: LayoutBuilderAdminRegistrar`, `schema-extender: LayoutBuilderAdminRegistrar`.
- Permissions: `ViewAny:Layout`, `View:Layout`, `Create:Layout`, `EditContent:Layout`, `EditLayout:Layout`, `Update:Layout`, `Delete:Layout`, `DeleteAny:Layout`, `Restore:Layout`, `ForceDelete:Layout`, `Replicate:Layout`, `Reorder:Layout`, `BulkMutate:Layout`; access also governed by package policies: `LayoutPresetPolicy`.
- Public routes: registers `LayoutBuilderRoutesContribution`.
- Database changes: package migrations are declared.
- Config: `config/capell-layout-builder.php`.
- Settings: no package settings declared.
- Queues or schedules: scheduled commands `capell:layout-builder:prune-bulk-change-runs (daily; manifest declared)`, `capell:widget-snapshots:prune (daily; manifest declared)`; queue jobs `ApplyLayoutBulkChangeRunJob`, `SyncLinkedLayoutPresetJob`.
- Cache tags: `layout-builder`.
- Commands: `capell:layout-builder-install`, `capell:layout-builder:prune-bulk-change-runs`.

## Common Pitfalls

- Keep required Capell packages on compatible v4 releases: `capell-app/core`, `capell-app/admin`, `capell-app/block-library`, `capell-app/frontend`.
- Run migrations before opening package resources or public routes.
- Review package configuration before production-like verification: `config/capell-layout-builder.php`.
- Keep the host Laravel scheduler running so package-registered schedules can execute: `capell:layout-builder:prune-bulk-change-runs (daily; manifest declared)`, `capell:widget-snapshots:prune (daily; manifest declared)`.
- Keep public Blade and cached HTML free of authoring markers, model IDs, permissions, signed editor URLs, and lazy database queries.
- Custom write integrations must preserve invalidation for `layout-builder` cache tags.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Admin screen or command fails on missing table | Package migrations have not run | Check the tables listed in `Data Model` | Run host migrations and rerun the focused package test |
| Background work does not run | Queue worker or declared schedule is not active | Check the jobs and scheduled commands listed in `Technical Shape` | Start the queue worker or host scheduler, then run the focused command or package test |
| Public output leaks unexpected state | Render data, cache variation, or authoring boundary has regressed | Check public Blade, cache tags, and public-output safety tests | Move data loading out of Blade and rerun the package public-output tests |

## Quick Start

1. Install the package: `composer require capell-app/layout-builder`.
2. Run the package setup: `php artisan capell:layout-builder-install`.
3. Open the package admin surface at `/screenshot-fixtures/layout-builder-admin-editor` and confirm Layout Builder is available.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Worked extension examples](docs/extension-contracts.md)
- [Admin guide](docs/admin-guide.md)
- Configuration files: [`config/capell-layout-builder.php`](config/capell-layout-builder.php).
- [Troubleshooting](#troubleshooting)
- [Screenshot contract](docs/screenshots.json)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Related packages: [Block Library](../block-library/README.md), [Content Sections](../content-sections/README.md), [Frontend Authoring](../frontend-authoring/README.md), [Publishing Studio](../publishing-studio/README.md), [Structured Content Library](../structured-content-library/README.md).
- Focused tests: `vendor/bin/pest packages/layout-builder/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
