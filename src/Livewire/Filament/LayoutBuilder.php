<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Filament\Contracts\HasPageResource;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Actions\GetResourceFromBlueprintAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\ApplyStarterLayoutPresetAction;
use Capell\LayoutBuilder\Actions\BuildLayoutBuilderTreeAction;
use Capell\LayoutBuilder\Actions\ListLayoutPresetsAction;
use Capell\LayoutBuilder\Actions\Mutations\CreateLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\Mutations\PasteLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\Mutations\PushLayoutMutationSnapshotAction;
use Capell\LayoutBuilder\Actions\Mutations\RedoLayoutMutationSnapshotAction;
use Capell\LayoutBuilder\Actions\Mutations\UndoLayoutMutationSnapshotAction;
use Capell\LayoutBuilder\Actions\PersistLayoutBuilderStateAction;
use Capell\LayoutBuilder\Actions\RenderAdminLayoutPreviewAction;
use Capell\LayoutBuilder\Actions\SaveLayoutPresetAction;
use Capell\LayoutBuilder\Data\AdminLayoutPreviewData;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutBuilderTreeData;
use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Capell\LayoutBuilder\Data\LayoutPresetData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutBuilderEditorMode;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\AuthorizesLayoutBuilderAccess;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\HasLayoutActions;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\ManagesAssets;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\ManagesContainers;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\ManagesLayoutBuilderState;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\ManagesWidgets;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Capell\LayoutBuilder\Support\LayoutClipboard;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session as SessionFacade;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use LogicException;
use Throwable;

/**
 * @property-read ?Pageable $page
 * @property-read mixed $changeLayoutAction
 * @property-read mixed $cloneLayoutForPageAction
 * @property-read mixed $duplicateLayoutAction
 * @property-read mixed $addWidgetAction
 * @property-read mixed $editWidgetAssetAction
 */
class LayoutBuilder extends Component implements HasActions, HasForms, HasPageResource
{
    use AuthorizesLayoutBuilderAccess;
    use HasLayoutActions;
    use InteractsWithActions;
    use InteractsWithForms;
    use ManagesAssets;
    use ManagesContainers;
    use ManagesLayoutBuilderState;
    use ManagesWidgets;

    private const int SERVER_SIDE_STATE_THRESHOLD_BYTES = 131072;

    #[Locked]
    public ?Pageable $page = null;

    #[Locked]
    public ?Site $site = null;

    #[Locked]
    public Layout $layout;

    /**
     * @var array<array-key, mixed>
     */
    #[Locked]
    public ?array $originalAssets = null;

    /**
     * @var array<int, string>
     */
    #[Locked]
    public array $knownContainerKeys = [];

    /**
     * @var array<string, array<string, mixed>>|null
     */
    public ?array $containers = null;

    /**
     * @var array<array-key, mixed>
     */
    public array $assets = [];

    /**
     * @var array<array-key, mixed>
     */
    public array $selectedRecords = [];

    public bool $layoutModified = false;

    /**
     * @var array<array-key, mixed>
     */
    public array $layoutDiagnostics = [];

    /**
     * @var array<array-key, mixed>
     */
    public array $layoutChanges = [];

    /**
     * @var array<array-key, mixed>
     */
    public ?array $savedBaselineSnapshot = null;

    /**
     * @var array<array-key, mixed>
     */
    public array $layoutUndoSnapshots = [];

    /**
     * @var array<array-key, mixed>
     */
    public array $layoutRedoSnapshots = [];

    public ?LayoutBreakpoint $activeBreakpoint = null;

    public string $editorMode = 'content_first';

    public ?string $returnToContentItemKey = null;

    public ?string $selectedContainerKey = null;

    public ?int $selectedWidgetIndex = null;

    public ?string $selectedPreviewNodeHandle = null;

    public string $visualPreviewSignature = '';

    public string $visualPreviewStatus = 'stale';

    public bool $layoutStateStoredServerSide = false;

    /**
     * @var array<string, array{type: string, containerKey: string, widgetIndex?: int}>
     */
    public array $visualPreviewNodeMap = [];

    /**
     * @var array<array-key, mixed>
     */
    protected array $containerWidgets;

    protected ?LayoutClipboard $layoutClipboard = null;

    protected ?AdminLayoutPreviewData $visualPreview = null;

    protected string $view = 'capell-layout-builder::livewire.filament.layout-builder.index';

    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Page);
    }

    public function mount(
        ?int $layoutId = null,
        ?int $siteId = null,
        ?int $pageId = null,
        ?string $pageClass = null,
        ?string $initialContainerKey = null,
        ?int $initialWidgetIndex = null,
    ): void {
        $this->resolveMountModels($layoutId, $siteId, $pageId, $pageClass);
        $this->assertLayoutMatchesPageSite();

        $this->editorMode = $this->resolveInitialEditorMode();
        $this->assertCanUseLayoutBuilder();

        $this->loadNew();
        $this->initializeVisualEditor($initialContainerKey, $initialWidgetIndex);
    }

    public function boot(): void
    {
        throw_if(! Filament::auth()->check(), AuthenticationException::class);
    }

    public function hydrate(): void
    {
        if (! $this->layoutStateStoredServerSide) {
            return;
        }

        $state = SessionFacade::get($this->serverSideLayoutStateKey());

        if (! is_array($state)) {
            $this->loadFromStore();
            $this->layoutStateStoredServerSide = false;

            return;
        }

        /** @var array<string, mixed> $state */
        $this->restoreServerSideLayoutState($state);
    }

    public function dehydrate(): void
    {
        if (! $this->shouldStoreLayoutStateServerSide()) {
            $this->layoutStateStoredServerSide = false;

            return;
        }

        SessionFacade::put($this->serverSideLayoutStateKey(), $this->serverSideLayoutStatePayload());

        $this->layoutStateStoredServerSide = true;
        $this->containers = [];
        $this->assets = [];
        $this->originalAssets = [];
        $this->selectedRecords = [];
        $this->savedBaselineSnapshot = [];
        $this->layoutUndoSnapshots = [];
        $this->layoutRedoSnapshots = [];
        $this->layoutDiagnostics = [];
        $this->layoutChanges = [];
        $this->visualPreviewNodeMap = [];
    }

    #[Computed]
    public function layoutPagesCount(): int
    {
        if ($this->layout->hasAttribute('pages_count')) {
            return $this->layout->pages_count;
        }

        $this->layout->loadCount('pages');

        return $this->layout->pages_count;
    }

    #[Computed]
    public function layoutIsUsedByPages(): bool
    {
        return $this->layoutPagesCount() > 0;
    }

    #[Computed]
    public function otherPagesUsingLayoutCount(): int
    {
        if ($this->page === null) {
            return $this->layoutPagesCount();
        }

        return max(0, $this->layoutPagesCount() - 1);
    }

    #[Computed]
    public function layoutIsSharedWithOtherPages(): bool
    {
        return $this->page !== null && $this->otherPagesUsingLayoutCount() > 0;
    }

    public function getPagesUsingLayoutUrl(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Page)::getUrl(parameters: [
            'tableFilters' => [
                'layout_id' => [
                    'value' => $this->layout->getKey(),
                ],
            ],
        ]);
    }

    #[On('save-layout')]
    public function saveLayout(bool $withNotifications = false): bool
    {
        $this->assertCanUpdateLayout();

        if (! $this->layoutModified) {
            $this->dispatch('capell-layout-builder-authoring-saved', status: 'published', redirectUrl: null);

            return true;
        }

        $this->loadFromStore();
        $this->assertKnownContainerStructure();
        $this->refreshLayoutDiagnostics();

        if ($this->hasBlockingLayoutDiagnostics()) {
            $this->addError('layoutDiagnostics', __('capell-layout-builder::message.layout_has_blocking_diagnostics'));

            return false;
        }

        PersistLayoutBuilderStateAction::run(
            layout: $this->layout,
            page: $this->page instanceof Model ? $this->page : null,
            containers: $this->containers,
            persistWidgetAssets: function (): void {
                $this->persistWidgetAssets();
            },
        );

        $this->dispatch('layout-builder-reset');

        $this->layoutUndoSnapshots = [];
        $this->layoutRedoSnapshots = [];
        $this->captureSavedBaselineState();
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();

        $this->layoutUpdated(false);

        if ($withNotifications) {
            Notification::make('layout-saved')
                ->body(__('capell-layout-builder::message.layout_saved'))
                ->success()
                ->send();
        }

        $this->dispatch('capell-layout-builder-authoring-saved', status: 'published', redirectUrl: null);

        return true;
    }

    /**
     * @param  array<int, int|string>  $widgets
     */
    #[On('add-widgets-to-container')]
    public function addWidgetsToContainer(string $containerKey, array $widgets, ?string $actionModalId = null, ?int $position = null): void
    {
        $this->assertCanUpdateLayout();
        $this->assertValidContainerKey($containerKey);

        if ($widgets === []) {
            Notification::make('no-widgets-selected')
                ->body(__('capell-layout-builder::message.no_widgets_selected'))
                ->warning()
                ->send();

            return;
        }

        $this->ensureLoaded();

        $targetPosition = $position;

        foreach ($widgets as $widgetId) {
            $widget = $this->getWidget($widgetId);

            $widgetIndex = $this->addWidgetToContainerAtPosition($widget, $containerKey, $targetPosition);

            if ($targetPosition !== null) {
                $targetPosition++;
            }

            $widget = $this->loadWidget($containerKey, $widgetIndex);

            $this->assets[$containerKey][$widgetIndex] = $this->mapWidgetAssets($widget, $containerKey);

            $this->updatePageAssets($containerKey, $widgetIndex);
        }

        session(['layout-builder.container' => $containerKey]);

        $this->setupSelectedAssets();

        $this->layoutUpdated();

        if ($actionModalId !== null && $actionModalId !== '') {
            $this->dispatch('close-modal', id: $actionModalId);
        }
    }

    /**
     * @param  array{containerKey: string, widgetIndex: int, hasPageAssets?: bool}  $arguments
     * @param  array<int, int|string>  $assets
     */
    #[On('sync-selected-assets')]
    public function addAssetsToWidget(array $arguments, string $type, array $assets): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $containerKey = $arguments['containerKey'];
        $widgetIndex = $arguments['widgetIndex'];
        $hasPageAssets = $arguments['hasPageAssets'] ?? false;

        $this->addAssets($containerKey, $widgetIndex, $hasPageAssets, $type, $assets);

        $this->layoutUpdated();
    }

    /**
     * @return class-string<resource>
     */
    public function getPageResource(): string
    {
        if ($this->page !== null) {
            $resource = GetResourceFromBlueprintAction::run(ResourceEnum::Page, $this->pageContext()->type);

            if (is_string($resource) && is_subclass_of($resource, Resource::class)) {
                return $resource;
            }
        }

        $resource = AdminSurfaceLookup::resource(ResourceEnum::Page);

        if (! is_subclass_of($resource, Resource::class)) {
            throw new LogicException(sprintf('Page resource [%s] must extend [%s].', $resource, Resource::class));
        }

        return $resource;
    }

    /**
     * @return class-string<resource>
     */
    public function getCurrentResource(): string
    {
        if ($this->inPageContext()) {
            return $this->getPageResource();
        }

        return AdminSurfaceLookup::resource(ResourceEnum::Layout);
    }

    /**
     * @param  array<array-key, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return resolve(Factory::class)->make('capell-admin::components.placeholder', $params);
    }

    public function render(): View
    {
        $this->ensureLoaded();
        $this->prepareVisualPreview();

        return resolve(Factory::class)->make($this->view);
    }

    #[Computed]
    public function layoutBuilderTree(): LayoutBuilderTreeData
    {
        $this->ensureLoaded();

        return BuildLayoutBuilderTreeAction::run(
            containers: $this->containers ?? [],
            containerWidgets: $this->containerWidgets ?? [],
            assets: $this->assets,
            page: $this->page,
            selectedContainerKey: $this->selectedContainerKey,
            selectedWidgetIndex: $this->selectedWidgetIndex,
        );
    }

    /**
     * @return list<LayoutPresetData>
     */
    #[Computed]
    public function starterLayoutPresets(): array
    {
        return ListLayoutPresetsAction::run();
    }

    #[Computed]
    public function selectedWidget(): ?Widget
    {
        if ($this->selectedContainerKey === null || $this->selectedWidgetIndex === null) {
            return null;
        }

        $selectedWidget = $this->containerWidgets[$this->selectedContainerKey][$this->selectedWidgetIndex] ?? null;

        return $selectedWidget instanceof Widget ? $selectedWidget : null;
    }

    public function selectContainer(string $containerKey): void
    {
        $this->ensureLoaded();

        if (! array_key_exists($containerKey, $this->containers ?? [])) {
            return;
        }

        $this->selectedContainerKey = $containerKey;
        $this->selectedWidgetIndex = null;
        $this->selectedPreviewNodeHandle = $this->handleForContainer($containerKey);
    }

    public function selectWidget(string $containerKey, int $widgetIndex): void
    {
        $this->ensureLoaded();

        if (! isset($this->containers[$containerKey]['widgets'][$widgetIndex])) {
            return;
        }

        $this->selectedContainerKey = $containerKey;
        $this->selectedWidgetIndex = $widgetIndex;
        $this->selectedPreviewNodeHandle = $this->handleForWidget($containerKey, $widgetIndex);
    }

    public function selectPreviewNode(string $handle): void
    {
        $node = $this->visualPreviewNodeMap[$handle] ?? $this->resolvePreviewNodeHandle($handle);

        if (! is_array($node)) {
            return;
        }

        if (($node['type'] ?? null) === 'widget' && isset($node['containerKey'], $node['widgetIndex'])) {
            $this->selectWidget($node['containerKey'], $node['widgetIndex']);

            return;
        }

        if (($node['type'] ?? null) === 'container' && isset($node['containerKey'])) {
            $this->selectContainer($node['containerKey']);
        }
    }

    /**
     * @param  array<string, mixed>  $pageFormState
     */
    #[On('refresh-layout-builder-visual-preview')]
    public function refreshVisualPreview(array $pageFormState = []): void
    {
        $this->prepareVisualPreview($pageFormState);
    }

    public function visualPreviewHtml(): string
    {
        return $this->prepareVisualPreview()->html;
    }

    public function showAdvancedLayout(?string $returnToContentItemKey = null): void
    {
        $this->assertCanEditLayout();

        $this->returnToContentItemKey = $returnToContentItemKey;
        $this->editorMode = LayoutBuilderEditorMode::LayoutFirst->value;

        $this->dispatch('layout-builder-editor-mode-changed', mode: $this->editorMode);
    }

    public function showContentEditor(): void
    {
        $this->assertCanEditContent();

        $this->editorMode = LayoutBuilderEditorMode::ContentFirst->value;

        $this->dispatch(
            'layout-builder-editor-mode-changed',
            mode: $this->editorMode,
            returnToContentItemKey: $this->returnToContentItemKey,
        );
    }

    /**
     * @return array<string, string>
     */
    public function layoutAreaOptions(): array
    {
        return resolve(LayoutAreaRegistry::class)->options($this->activeThemeKey());
    }

    /**
     * @param  array<array-key, mixed>  $container
     */
    public function layoutAreaForContainer(array $container): string
    {
        return resolve(LayoutAreaRegistry::class)->containerArea($container);
    }

    public function layoutAreaLabel(string $area): string
    {
        return resolve(LayoutAreaRegistry::class)->label($area, $this->activeThemeKey());
    }

    public function undoLayoutMutation(): void
    {
        $this->assertCanUpdateLayout();

        if ($this->layoutUndoSnapshots === []) {
            return;
        }

        $navigation = UndoLayoutMutationSnapshotAction::run(
            currentState: $this->layoutState(),
            undoSnapshots: $this->layoutUndoSnapshots,
            redoSnapshots: $this->layoutRedoSnapshots,
        );

        $this->layoutUndoSnapshots = $navigation->history->undoSnapshots;
        $this->layoutRedoSnapshots = $navigation->history->redoSnapshots;

        $state = $navigation->state;
        if (! $state instanceof LayoutBuilderStateData) {
            return;
        }

        $this->applyLayoutState($state, markModified: true);
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();
        $this->refreshVisualPreview();
    }

    public function redoLayoutMutation(): void
    {
        $this->assertCanUpdateLayout();

        if ($this->layoutRedoSnapshots === []) {
            return;
        }

        $navigation = RedoLayoutMutationSnapshotAction::run(
            currentState: $this->layoutState(),
            undoSnapshots: $this->layoutUndoSnapshots,
            redoSnapshots: $this->layoutRedoSnapshots,
        );

        $this->layoutUndoSnapshots = $navigation->history->undoSnapshots;
        $this->layoutRedoSnapshots = $navigation->history->redoSnapshots;

        $state = $navigation->state;
        if (! $state instanceof LayoutBuilderStateData) {
            return;
        }

        $this->applyLayoutState($state, markModified: true);
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();
        $this->refreshVisualPreview();
    }

    public function setActiveBreakpoint(?string $breakpoint): void
    {
        $this->activeBreakpoint = LayoutBreakpoint::fromNullable($breakpoint);
    }

    public function resetResponsiveContainerOverride(string $containerKey): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        if (! $this->activeBreakpoint instanceof LayoutBreakpoint || ! isset($this->containers[$containerKey])) {
            return;
        }

        $history = PushLayoutMutationSnapshotAction::run($this->layoutState(), $this->layoutUndoSnapshots);
        $this->layoutUndoSnapshots = $history->undoSnapshots;
        $this->layoutRedoSnapshots = $history->redoSnapshots;

        unset($this->containers[$containerKey]['meta']['responsive'][$this->activeBreakpoint->value]);

        if (($this->containers[$containerKey]['meta']['responsive'] ?? []) === []) {
            $this->containers[$containerKey]['meta']['responsive'] = [];
        }

        $this->layoutUpdated();
    }

    public function copyLayoutContainer(string $containerKey): void
    {
        $this->assertCanUpdateLayout();
        $this->ensureLoaded();

        if (! isset($this->containers[$containerKey])) {
            return;
        }

        $this->clipboard()->copy(CreateLayoutFragmentAction::run($this->layoutState(), $containerKey, null));
    }

    public function copyLayoutWidget(string $containerKey, int $widgetIndex): void
    {
        $this->assertCanUpdateLayout();
        $this->ensureLoaded();

        if (! isset($this->containers[$containerKey]['widgets'][$widgetIndex])) {
            return;
        }

        $this->clipboard()->copy(CreateLayoutFragmentAction::run($this->layoutState(), $containerKey, $widgetIndex));
    }

    public function pasteLayoutFragment(string $targetContainerKey, ?int $targetIndex = null): void
    {
        $this->assertCanUpdateLayout();
        $this->ensureLoaded();
        $this->assertValidContainerKey($targetContainerKey);

        $fragment = $this->clipboard()->current();

        if (! $fragment instanceof LayoutFragmentData) {
            return;
        }

        $knownContainerKeys = array_keys($this->containers ?? []);

        $this->applyLayoutMutationResult(PasteLayoutFragmentAction::run(
            state: $this->layoutState(),
            fragment: $fragment,
            targetContainerKey: $targetContainerKey,
            targetIndex: $targetIndex,
        ));

        $this->trackNewContainerKeysSince($knownContainerKeys);
    }

    public function saveLayoutPreset(string $containerKey, string $name): void
    {
        $this->assertCanUpdateLayout();
        $this->ensureLoaded();
        $this->assertValidContainerKey($containerKey);

        $container = $this->containers[$containerKey] ?? null;

        if (! is_array($container)) {
            Notification::make('layout-preset-container-missing')
                ->body(__('capell-layout-builder::message.no_container_selected'))
                ->warning()
                ->send();

            return;
        }

        $name = trim($name);

        if ($name === '') {
            Notification::make('layout-preset-name-required')
                ->body(__('capell-layout-builder::message.layout_preset_name_required'))
                ->warning()
                ->send();

            return;
        }

        if (! $this->site instanceof Site) {
            Notification::make('layout-preset-site-missing')
                ->body(__('capell-layout-builder::message.site_missing_warning'))
                ->warning()
                ->send();

            return;
        }

        $this->assertCanCreateLayoutPreset($this->site);

        try {
            SaveLayoutPresetAction::run(
                layout: $this->layout,
                site: $this->site,
                name: $name,
                category: $containerKey,
                containers: [$containerKey => $container],
            );
        } catch (InvalidArgumentException) {
            Notification::make('layout-preset-save-failed')
                ->body(__('capell-layout-builder::message.layout_preset_name_required'))
                ->warning()
                ->send();

            return;
        } catch (LogicException $exception) {
            $isDuplicatePresetName = str_contains($exception->getMessage(), 'already exists');

            Notification::make('layout-preset-save-failed')
                ->body($isDuplicatePresetName
                    ? __('capell-layout-builder::message.layout_preset_duplicate')
                    : __('capell-layout-builder::message.layout_preset_save_failed'))
                ->warning()
                ->send();

            return;
        }

        Notification::make('layout-preset-saved')
            ->body(__('capell-layout-builder::message.layout_preset_saved'))
            ->success()
            ->send();
    }

    public function insertLayoutPreset(string $name, string $targetContainerKey): void
    {
        $this->assertCanUpdateLayout();
        $this->ensureLoaded();
        $this->assertValidContainerKey($targetContainerKey);

        if (! $this->site instanceof Site) {
            Notification::make('layout-preset-site-missing')
                ->body(__('capell-layout-builder::message.site_missing_warning'))
                ->warning()
                ->send();

            return;
        }

        $preset = LayoutPreset::query()
            ->forSite($this->site)
            ->where(fn (EloquentBuilder $query): EloquentBuilder => $this->wherePresetNameOrKey($query, $name))
            ->first();

        if (! $preset instanceof LayoutPreset) {
            Notification::make('layout-preset-missing')
                ->body(__('capell-layout-builder::message.layout_preset_not_found'))
                ->warning()
                ->send();

            return;
        }

        $snapshot = is_array($preset->snapshot) ? $preset->snapshot : [];
        $this->assertCanApplyLayoutPreset($preset, $this->site);

        $containers = is_array($snapshot['containers'] ?? null) ? $snapshot['containers'] : [];
        $containers = resolve(SaveLayoutPresetAction::class)->sanitizePresetContainers($containers);

        $sourceContainerKey = array_key_first($containers);
        $container = is_string($sourceContainerKey) ? ($containers[$sourceContainerKey] ?? null) : null;

        if (! is_string($sourceContainerKey) || ! is_array($container)) {
            Notification::make('layout-preset-invalid')
                ->body(__('capell-layout-builder::message.layout_preset_invalid'))
                ->warning()
                ->send();

            return;
        }

        $fragment = new LayoutFragmentData(
            sourceContainerKey: $sourceContainerKey,
            sourceWidgetIndex: null,
            container: $container,
            widget: null,
        );

        $knownContainerKeys = array_keys($this->containers ?? []);

        $this->applyLayoutMutationResult(PasteLayoutFragmentAction::run(
            state: $this->layoutState(),
            fragment: $fragment,
            targetContainerKey: $targetContainerKey,
            targetIndex: null,
        ));

        $this->trackNewContainerKeysSince($knownContainerKeys);
    }

    public function applyStarterLayoutPreset(string $presetKey): void
    {
        $this->assertCanUpdateLayout();
        $this->ensureLoaded();

        $knownContainerKeys = array_keys($this->containers ?? []);

        try {
            $this->applyLayoutMutationResult(ApplyStarterLayoutPresetAction::run($presetKey));
        } catch (InvalidArgumentException) {
            Notification::make('starter-layout-preset-missing')
                ->body(__('capell-layout-builder::message.starter_layout_preset_not_found'))
                ->warning()
                ->send();

            return;
        }

        $this->trackNewContainerKeysSince($knownContainerKeys);

        Notification::make('starter-layout-preset-applied')
            ->body(__('capell-layout-builder::message.starter_layout_preset_applied'))
            ->success()
            ->send();
    }

    protected function initializeVisualEditor(?string $initialContainerKey = null, ?int $initialWidgetIndex = null): void
    {
        if (
            $initialContainerKey !== null
            && $initialWidgetIndex !== null
            && isset($this->containers[$initialContainerKey]['widgets'][$initialWidgetIndex])
        ) {
            $this->selectedContainerKey = $initialContainerKey;
            $this->selectedWidgetIndex = $initialWidgetIndex;
            $this->selectedPreviewNodeHandle = $this->handleForWidget($initialContainerKey, $initialWidgetIndex);
        } else {
            $firstContainerKey = array_key_first($this->containers ?? []);
            $this->selectedContainerKey ??= is_string($firstContainerKey) ? $firstContainerKey : null;
            $this->selectedWidgetIndex = null;
            $this->selectedPreviewNodeHandle = $this->selectedContainerKey === null
                ? null
                : $this->handleForContainer($this->selectedContainerKey);
        }

        $this->refreshVisualPreview();
    }

    /**
     * @param  array<string, mixed>  $pageFormState
     */
    private function prepareVisualPreview(array $pageFormState = []): AdminLayoutPreviewData
    {
        $this->assertCanUseLayoutBuilder();
        $this->ensureLoaded();

        if ($this->visualPreview instanceof AdminLayoutPreviewData && $pageFormState === []) {
            return $this->visualPreview;
        }

        $this->visualPreviewStatus = 'refreshing';
        $containers = $this->containers ?? [];
        $containerWidgets = $this->containerWidgets ?? [];
        $assets = $this->assets;

        /** @var array<string, array<string, mixed>> $containers */
        /** @var array<string, array<int, Widget>> $containerWidgets */
        /** @var array<string, array<int, array<int, array<string, mixed>>>> $assets */
        try {
            $preview = RenderAdminLayoutPreviewAction::run(
                containers: $containers,
                containerWidgets: $containerWidgets,
                assets: $assets,
                page: $this->page,
                pageFormState: $pageFormState,
            );
            $this->visualPreview = $preview;
        } catch (Throwable $throwable) {
            report($throwable);
            $this->visualPreviewStatus = 'error';

            return new AdminLayoutPreviewData(
                html: '',
                signature: $this->visualPreviewSignature,
                nodeMap: $this->visualPreviewNodeMap,
            );
        }

        $this->visualPreviewSignature = $preview->signature;
        $this->visualPreviewNodeMap = $preview->nodeMap;
        $this->selectedPreviewNodeHandle = $this->selectedPreviewNodeHandle !== null
            && array_key_exists($this->selectedPreviewNodeHandle, $this->visualPreviewNodeMap)
                ? $this->selectedPreviewNodeHandle
                : $this->defaultPreviewNodeHandle();
        $this->visualPreviewStatus = 'current';

        return $preview;
    }

    private function shouldStoreLayoutStateServerSide(): bool
    {
        $encodedState = json_encode($this->serverSideLayoutStatePayload());

        return is_string($encodedState)
            && strlen($encodedState) >= self::SERVER_SIDE_STATE_THRESHOLD_BYTES;
    }

    /**
     * @return array<string, mixed>
     */
    private function serverSideLayoutStatePayload(): array
    {
        return [
            'containers' => $this->containers ?? [],
            'assets' => $this->assets,
            'originalAssets' => $this->originalAssets ?? [],
            'selectedRecords' => $this->selectedRecords,
            'savedBaselineSnapshot' => $this->savedBaselineSnapshot ?? [],
            'layoutUndoSnapshots' => $this->layoutUndoSnapshots,
            'layoutRedoSnapshots' => $this->layoutRedoSnapshots,
            'layoutDiagnostics' => $this->layoutDiagnostics,
            'layoutChanges' => $this->layoutChanges,
            'visualPreviewNodeMap' => $this->visualPreviewNodeMap,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function restoreServerSideLayoutState(array $state): void
    {
        $this->containers = $this->normalizeRestoredContainers($state['containers'] ?? null);
        $this->assets = is_array($state['assets'] ?? null) ? $state['assets'] : [];
        $this->originalAssets = is_array($state['originalAssets'] ?? null) ? $state['originalAssets'] : [];
        $this->selectedRecords = is_array($state['selectedRecords'] ?? null) ? $state['selectedRecords'] : [];
        $this->savedBaselineSnapshot = is_array($state['savedBaselineSnapshot'] ?? null) ? $state['savedBaselineSnapshot'] : [];
        $this->layoutUndoSnapshots = is_array($state['layoutUndoSnapshots'] ?? null) ? $state['layoutUndoSnapshots'] : [];
        $this->layoutRedoSnapshots = is_array($state['layoutRedoSnapshots'] ?? null) ? $state['layoutRedoSnapshots'] : [];
        $this->layoutDiagnostics = is_array($state['layoutDiagnostics'] ?? null) ? $state['layoutDiagnostics'] : [];
        $this->layoutChanges = is_array($state['layoutChanges'] ?? null) ? $state['layoutChanges'] : [];
        $this->visualPreviewNodeMap = $this->normalizeVisualPreviewNodeMap($state['visualPreviewNodeMap'] ?? null);

        $this->rebuildLoadedContainerWidgets();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function normalizeRestoredContainers(mixed $containers): array
    {
        if (! is_array($containers)) {
            return [];
        }

        $normalized = [];

        foreach ($containers as $containerKey => $container) {
            if (! is_string($containerKey) || ! is_array($container)) {
                continue;
            }

            $normalizedContainer = [];

            foreach ($container as $attributeKey => $attributeValue) {
                if (! is_string($attributeKey)) {
                    continue;
                }

                $normalizedContainer[$attributeKey] = $attributeValue;
            }

            $normalized[$containerKey] = $normalizedContainer;
        }

        return $normalized;
    }

    /**
     * @return array<string, array{type: string, containerKey: string, widgetIndex?: int}>
     */
    private function normalizeVisualPreviewNodeMap(mixed $nodeMap): array
    {
        if (! is_array($nodeMap)) {
            return [];
        }

        $normalized = [];

        foreach ($nodeMap as $handle => $node) {
            if (! is_string($handle)) {
                continue;
            }

            if (! is_array($node)) {
                continue;
            }

            if (! is_string($node['type'] ?? null)) {
                continue;
            }

            if (! is_string($node['containerKey'] ?? null)) {
                continue;
            }

            $normalized[$handle] = [
                'type' => $node['type'],
                'containerKey' => $node['containerKey'],
            ];

            if (is_int($node['widgetIndex'] ?? null)) {
                $normalized[$handle]['widgetIndex'] = $node['widgetIndex'];
            }
        }

        return $normalized;
    }

    private function serverSideLayoutStateKey(): string
    {
        return sprintf('capell-layout-builder:%s:layout-state', $this->getId());
    }

    private function defaultPreviewNodeHandle(): ?string
    {
        if ($this->selectedContainerKey !== null && $this->selectedWidgetIndex !== null) {
            return $this->handleForWidget($this->selectedContainerKey, $this->selectedWidgetIndex);
        }

        if ($this->selectedContainerKey !== null) {
            return $this->handleForContainer($this->selectedContainerKey);
        }

        return null;
    }

    private function handleForContainer(string $containerKey): string
    {
        return hash('xxh128', 'container:' . $containerKey);
    }

    private function handleForWidget(string $containerKey, int $widgetIndex): string
    {
        return hash('xxh128', 'widget:' . $containerKey . ':' . $widgetIndex);
    }

    /**
     * @return array{type: string, containerKey: string, widgetIndex?: int}|null
     */
    private function resolvePreviewNodeHandle(string $handle): ?array
    {
        foreach ($this->containers ?? [] as $containerKey => $container) {
            $normalizedContainerKey = (string) $containerKey;

            if ($this->handleForContainer($normalizedContainerKey) === $handle) {
                return [
                    'type' => 'container',
                    'containerKey' => $normalizedContainerKey,
                ];
            }

            $widgets = is_array($container) && is_array($container['widgets'] ?? null)
                ? $container['widgets']
                : [];

            foreach (array_keys($widgets) as $widgetIndex) {
                if (! is_int($widgetIndex) && ! ctype_digit($widgetIndex)) {
                    continue;
                }

                $normalizedWidgetIndex = (int) $widgetIndex;

                if ($this->handleForWidget($normalizedContainerKey, $normalizedWidgetIndex) !== $handle) {
                    continue;
                }

                return [
                    'type' => 'widget',
                    'containerKey' => $normalizedContainerKey,
                    'widgetIndex' => $normalizedWidgetIndex,
                ];
            }
        }

        return null;
    }

    private function resolveMountModels(
        ?int $layoutId,
        ?int $siteId,
        ?int $pageId,
        ?string $pageClass,
    ): void {
        if (! isset($this->layout) && $layoutId !== null) {
            $layout = Layout::query()
                ->withCount('pages')
                ->find($layoutId);

            throw_if(
                ! $layout instanceof Layout,
                InvalidArgumentException::class,
                'Layout Builder requires a valid layout.',
            );

            $this->layout = $layout;
        }

        throw_if(
            ! isset($this->layout),
            InvalidArgumentException::class,
            'Layout Builder requires a layout.',
        );

        if (! $this->site instanceof Site && $siteId !== null) {
            $this->site = Site::query()->find($siteId);
        }

        if ($this->page === null && $pageId !== null && $pageClass !== null) {
            throw_unless(
                is_a($pageClass, Model::class, true) && is_a($pageClass, Pageable::class, true),
                InvalidArgumentException::class,
                'Layout Builder requires a valid page class.',
            );

            /** @var class-string<Model&Pageable> $pageClass */
            $page = $pageClass::query()->find($pageId);

            if ($page instanceof Pageable) {
                $this->page = $page;
            }
        }

        if (! $this->site instanceof Site && $this->page instanceof Model && $this->pageContext()->hasAttribute('site_id')) {
            $pageSiteId = $this->pageContext()->getAttribute('site_id');

            if (is_numeric($pageSiteId)) {
                $this->site = Site::query()->find((int) $pageSiteId);
            }
        }

        if (! $this->site instanceof Site && $this->layout->hasAttribute('site_id') && $this->layout->site_id !== null) {
            $this->site = Site::query()->find((int) $this->layout->site_id);
        }
    }

    /**
     * @param  EloquentBuilder<LayoutPreset>  $query
     * @return EloquentBuilder<LayoutPreset>
     */
    private function wherePresetNameOrKey(EloquentBuilder $query, string $name): EloquentBuilder
    {
        return $query
            ->where('name', $name)
            ->orWhere('key', $name);
    }
}
