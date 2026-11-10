# Layout Builder reference

<!-- prettier-ignore-start -->

Developer reference for `capell-app/layout-builder`. Start with the [README](../README.md) for what the extension does and how to install it.

## Package Details

Layout Builder is an **Available**, **Schema-owning** Capell package in the **Capell Essentials** product group. It ships as `capell-app/layout-builder` and extends these surfaces: admin, frontend, console.

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/layout-builder`
- Namespace: `Capell\LayoutBuilder`
- Theme key: not applicable

## Technical Shape

### Service providers

- `Capell\LayoutBuilder\Providers\MigrationMetadataServiceProvider`
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
- `WidgetFileUpload`
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
- `WidgetPolicy`

### Extension contracts

- `PublicLayoutWidgetAssetsRenderer`
- `LayoutContainerSchemaExtender`
- `WidgetAssetSchemaExtender`
- `WidgetSchemaExtender`
- `LayoutContainerThemePresentationProjector`
- `LayoutContentGroupContributor`
- `LayoutEditorState`
- `LayoutSidebarWidgetContributor`
- `PublicLayoutAssetMembership`
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
- `HasPageScopedAssetBatches`
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
- `LoadLayoutWidgetsAction`
- `MakeIndependentWidgetCopyAction`
- `MakeWidgetAction`
- `MergeWidgetMetadataAction`
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
- `ResolvePageContentStructureAction`
- `ResolvePublicWidgetAssetsAction`
- `ResolvePublicWidgetRenderContextAction`
- `ResolveWidgetPickerPreviewAction`
- `ResolveWidgetPresentationDataAction`
- `RunLinkedLayoutPresetSyncAction`
- `SaveFormComponentRelationshipAction`
- `SaveLayoutPresetAction`
- `SeedLayoutBuilderScreenshotFixtureAction`
- `SeedWidgetIntegrityScreenshotFixturesAction`
- `SetupLayoutBuilderPackageAction`
- `StripLayoutPresetLinkAction`
- `SummarizeLayoutChangesAction`
- `SyncLayoutPresetUsagesAction`
- `UpdateLinkedLayoutPresetItemAction`
- `WidgetContractValidatorAction`
- `BuildPublicWidgetPayloadsAction`
- `BuildWidgetExtensionResourceUsagesAction`
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
- `LoadedLayoutWidgetData`
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
- `WidgetPickerPreviewData`
- `WidgetScaffoldData`
- `WidgetSnapshotLocatorData`

### Jobs

- `ApplyLayoutBulkChangeRunJob`
- `SyncLinkedLayoutPresetJob`

### Command signatures

- `capell:layout-builder-install`
- `capell:layout-builder-widget-visual-regression`
- `capell:layout-builder:prune-bulk-change-runs`
- `capell:layouts:bulk-change`
- `capell:widget-snapshots:prune`

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
- `SeedLayoutBuilderScreenshotFixtureCommand`
- `WidgetVisualRegressionCommand`

### Manifest contributions

- `admin-resource: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`
- `asset: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`
- `configurator: Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar`
- `console-command: Capell\LayoutBuilder\Manifest\LayoutBuilderConsoleCommandsContribution`
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
- `packages/layout-builder/resources/views/components/layout-widgets/extension-runtime.blade.php`
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

<!-- prettier-ignore-end -->
