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
use Capell\LayoutBuilder\Actions\AnalyzeLayoutDiagnosticsAction;
use Capell\LayoutBuilder\Actions\BuildLayoutContentInventoryAction;
use Capell\LayoutBuilder\Actions\Mutations\CreateLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\Mutations\PasteLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\PersistLayoutBuilderStateAction;
use Capell\LayoutBuilder\Actions\SummarizeLayoutChangesAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryData;
use Capell\LayoutBuilder\Data\LayoutFragmentData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutBuilderEditorMode;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\HasLayoutActions;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\ManagesAssets;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\ManagesContainers;
use Capell\LayoutBuilder\Livewire\Filament\Concerns\ManagesElements;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Support\LayoutBuilderConfiguration;
use Capell\LayoutBuilder\Support\LayoutClipboard;
use Capell\LayoutBuilder\Support\LayoutPresetRepository;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read ?Pageable $page
 * @property-read mixed $changeLayoutAction
 * @property-read mixed $cloneLayoutForPageAction
 * @property-read mixed $duplicateLayoutAction
 * @property-read mixed $addElementAction
 * @property-read mixed $editElementAssetAction
 */
class LayoutBuilder extends Component implements HasActions, HasForms, HasPageResource
{
    use HasLayoutActions;
    use InteractsWithActions;
    use InteractsWithForms;
    use ManagesAssets;
    use ManagesContainers;
    use ManagesElements;

    #[Locked]
    public ?Pageable $page = null;

    #[Locked]
    public ?Site $site = null;

    #[Locked]
    public Layout $layout;

    #[Locked]
    public ?array $originalAssets = null;

    #[Locked]
    public array $knownContainerKeys = [];

    public ?array $containers = null;

    public array $assets = [];

    public array $selectedRecords;

    public bool $layoutModified = false;

    public array $layoutDiagnostics = [];

    public array $layoutChanges = [];

    public ?array $savedBaselineSnapshot = null;

    public array $layoutUndoSnapshots = [];

    public array $layoutRedoSnapshots = [];

    public ?LayoutBreakpoint $activeBreakpoint = null;

    public string $editorMode = 'content_first';

    public ?string $returnToContentItemKey = null;

    protected array $containerElements;

    protected ?LayoutClipboard $layoutClipboard = null;

    protected string $view = 'capell-layout-builder::livewire.filament.layout-builder.index';

    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Page);
    }

    public function mount(): void
    {
        $this->assertLayoutMatchesPageSite();

        $this->editorMode = $this->resolveInitialEditorMode();
        $this->assertCanUseLayoutBuilder();

        $this->loadNew();
    }

    public function boot(): void
    {
        throw_if(! Filament::auth()->check(), AuthenticationException::class);
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

        return $this->layout
            ->pages()
            ->whereKeyNot($this->page->getKey())
            ->count();
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
            persistElementAssets: function (): void {
                $this->persistElementAssets();
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

        return true;
    }

    #[On('add-elements-to-container')]
    public function addElementsToContainer(string $containerKey, array $elements, ?string $actionModalId = null, ?int $position = null): void
    {
        $this->assertCanUpdateLayout();
        $this->assertValidContainerKey($containerKey);

        if ($elements === []) {
            Notification::make('no-elements-selected')
                ->body(__('capell-layout-builder::message.no_elements_selected'))
                ->warning()
                ->send();

            return;
        }

        $this->ensureLoaded();

        $targetPosition = $position;

        foreach ($elements as $elementId) {
            $element = $this->getElement($elementId);

            $elementIndex = $this->addElementToContainerAtPosition($element, $containerKey, $targetPosition);

            if ($targetPosition !== null) {
                $targetPosition++;
            }

            $element = $this->loadElement($containerKey, $elementIndex);

            $this->assets[$containerKey][$elementIndex] = $this->mapElementAssets($element, $containerKey);

            $this->updatePageAssets($containerKey, $elementIndex);
        }

        session(['layout-builder.container' => $containerKey]);

        $this->setupSelectedAssets();

        $this->layoutUpdated();

        if ($actionModalId !== null && $actionModalId !== '') {
            $this->dispatch('close-modal', id: $actionModalId);
        }
    }

    #[On('sync-selected-assets')]
    public function addAssetsToElement(array $arguments, string $type, array $assets): void
    {
        $this->assertCanUpdateLayout();

        $this->ensureLoaded();

        $containerKey = $arguments['containerKey'];
        $elementIndex = $arguments['elementIndex'];
        $hasPageAssets = $arguments['hasPageAssets'] ?? false;

        $this->addAssets($containerKey, $elementIndex, $hasPageAssets, $type, $assets);

        $this->layoutUpdated();
    }

    /**
     * @return class-string<resource>
     */
    public function getPageResource(): string
    {
        if ($this->page !== null) {
            $resource = GetResourceFromBlueprintAction::run(ResourceEnum::Page, $this->page->type);

            if ($resource !== null) {
                return $resource;
            }
        }

        return AdminSurfaceLookup::resource(ResourceEnum::Page);
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

    public function placeholder(array $params = []): View
    {
        return view('capell-admin::components.placeholder', $params);
    }

    public function render(): View
    {
        $this->ensureLoaded();

        return view($this->view);
    }

    #[Computed]
    public function contentInventory(): LayoutContentInventoryData
    {
        $this->ensureLoaded();

        return BuildLayoutContentInventoryAction::run(
            layout: $this->layout,
            page: $this->page,
            containers: $this->containers ?? [],
            containerElements: $this->containerElements ?? [],
            assets: $this->assets,
            signature: $this->contentInventorySignature(),
            siteName: $this->site?->name,
        );
    }

    public function contentInventorySignature(): string
    {
        $payload = [
            'layout' => $this->layout->getKey(),
            'layout_updated_at' => $this->layout->updated_at?->getTimestamp(),
            'containers' => $this->containers ?? [],
            'assets' => $this->contentInventorySignatureAssets(),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
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

    public function canEditContent(): bool
    {
        return $this->canPerformLayoutBuilderAbility('editContent');
    }

    public function canEditLayout(): bool
    {
        return $this->canPerformLayoutBuilderAbility('editLayout');
    }

    public function undoLayoutMutation(): void
    {
        $this->assertCanUpdateLayout();

        if ($this->layoutUndoSnapshots === []) {
            return;
        }

        $this->layoutRedoSnapshots[] = $this->layoutState()->toLivewirePayload();

        $snapshot = array_pop($this->layoutUndoSnapshots);

        $this->applyLayoutState($this->stateFromSnapshot($snapshot), markModified: true);
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();
    }

    public function redoLayoutMutation(): void
    {
        $this->assertCanUpdateLayout();

        if ($this->layoutRedoSnapshots === []) {
            return;
        }

        $this->layoutUndoSnapshots[] = $this->layoutState()->toLivewirePayload();

        $snapshot = array_pop($this->layoutRedoSnapshots);

        $this->applyLayoutState($this->stateFromSnapshot($snapshot), markModified: true);
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();
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

        $this->layoutUndoSnapshots[] = $this->layoutState()->toLivewirePayload();
        $this->layoutRedoSnapshots = [];

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

    public function copyLayoutElement(string $containerKey, int $elementIndex): void
    {
        $this->assertCanUpdateLayout();
        $this->ensureLoaded();

        if (! isset($this->containers[$containerKey]['elements'][$elementIndex])) {
            return;
        }

        $this->clipboard()->copy(CreateLayoutFragmentAction::run($this->layoutState(), $containerKey, $elementIndex));
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

    public function saveLayoutPreset(string $containerKey, string $name, string $description): void
    {
        $this->assertCanUpdateLayout();
        $this->ensureLoaded();
        $this->assertValidContainerKey($containerKey);

        if (! isset($this->containers[$containerKey])) {
            return;
        }

        $name = trim($name);

        if ($name === '') {
            return;
        }

        $fragment = CreateLayoutFragmentAction::run($this->layoutState(), $containerKey, null);

        resolve(LayoutPresetRepository::class)->put($name, $description, $fragment);
    }

    public function insertLayoutPreset(string $name, string $targetContainerKey): void
    {
        $this->assertCanUpdateLayout();
        $this->ensureLoaded();
        $this->assertValidContainerKey($targetContainerKey);

        $fragment = resolve(LayoutPresetRepository::class)->find($name);

        if ($fragment === null) {
            return;
        }

        $knownContainerKeys = array_keys($this->containers ?? []);

        $this->applyLayoutMutationResult(PasteLayoutFragmentAction::run(
            state: $this->layoutState(),
            fragment: $fragment,
            targetContainerKey: $targetContainerKey,
            targetIndex: null,
        ));

        $this->trackNewContainerKeysSince($knownContainerKeys);
    }

    protected function persistElementAssets(): void
    {
        $processedElementKeys = [];

        foreach ($this->containers as $containerKey => $container) {
            foreach ($container['elements'] as $elementIndex => $element) {
                if ($this->inPageContext() && isset($element['pageable_type'], $element['pageable_id'])) {
                    $key = $element['element_key'] . '_' . $element['pageable_type'] . '_' . $element['pageable_id'] . '_' . $element['container'] . '_' . $element['occurrence'];
                } else {
                    $key = $element['element_key'] . '_' . $element['occurrence'];
                }

                if (in_array($key, $processedElementKeys, true)) {
                    continue;
                }

                $processedElementKeys[] = $key;

                $this->updateAssets($containerKey, $elementIndex, $element['old_container'] ?? null);
            }
        }

        if ($this->inPageContext()) {
            $this->deleteRemovedElementAssets();
        }
    }

    protected function clipboard(): LayoutClipboard
    {
        return $this->layoutClipboard ??= new LayoutClipboard;
    }

    protected function ensureLoaded(): void
    {
        if (! isset($this->containerElements)) {
            $this->loadFromStore();
        }
    }

    protected function loadNew(): void
    {
        $this->setupContainers();

        $elements = $this->preloadAllElements();

        foreach (array_keys($this->containers) as $containerKey) {
            $this->setupContainerElements($containerKey, $elements);
        }

        $this->setupSelectedAssets();

        $this->saveOriginalAssets();
        $this->captureSavedBaselineState();
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();
    }

    protected function loadFromStore(): void
    {
        $this->setupContainers();

        $elements = $this->preloadAllElements(withAssets: false);

        $allElementAssets = $this->preloadAllElementAssets();

        $containerElementAssets = [];

        foreach ($this->assets as $containerKey => $containerElements) {
            foreach ($containerElements as $elementIndex => $elementAssets) {
                $elementKey = $this->containers[$containerKey]['elements'][$elementIndex]['element_key'] ?? null;

                if ($elementKey === null) {
                    continue;
                }

                /** @var Element $element */
                $element = $elements[$elementKey];

                $containerElementAssets[$containerKey][$elementIndex] = $this->setupElementAssets(
                    $containerKey,
                    $elementIndex,
                    $elementAssets,
                    $allElementAssets,
                    $element,
                );
            }
        }

        foreach (array_keys($this->containers) as $containerKey) {
            $this->setupContainerElements($containerKey, $elements, $containerElementAssets);
        }
    }

    protected function reload(): void
    {
        $this->reset('containerElements', 'selectedRecords', 'assets', 'originalAssets', 'containers', 'layout');

        $this->loadNew();
    }

    protected function inPageContext(): bool
    {
        return $this->page instanceof Pageable;
    }

    protected function assertCanUpdateLayout(): void
    {
        $this->assertCanEditLayout();
    }

    protected function assertCanUseLayoutBuilder(): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);

        $this->assertLayoutMatchesPageSite();

        if ($this->canEditContent() || $this->canEditLayout()) {
            return;
        }

        $this->assertCanEditLayout();
    }

    protected function assertCanEditContent(): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);

        $this->assertLayoutMatchesPageSite();

        if ($this->page instanceof Model) {
            $this->authorizeLayoutBuilderAbility($actor, 'editContent', $this->page);

            return;
        }

        $this->authorizeLayoutBuilderAbility($actor, 'editContent', $this->layout);
    }

    protected function assertCanEditLayout(): void
    {
        $actor = Filament::auth()->user();

        throw_if($actor === null, AuthenticationException::class);

        $this->assertLayoutMatchesPageSite();

        if ($this->page instanceof Model) {
            $this->authorizeLayoutBuilderAbility($actor, 'editLayout', $this->page);
        }

        $this->authorizeLayoutBuilderAbility($actor, 'editLayout', $this->layout);
    }

    protected function assertLayoutMatchesPageSite(): void
    {
        if (! $this->page instanceof Model) {
            return;
        }

        if (! $this->layout->hasAttribute('site_id') || $this->layout->site_id === null) {
            return;
        }

        if (! $this->page->hasAttribute('site_id') || $this->page->site_id === $this->layout->site_id) {
            return;
        }

        throw new AuthorizationException;
    }

    protected function layoutUpdated(bool $modified = true): void
    {
        $this->layoutModified = $modified;
    }

    protected function applyLayoutMutationResult(LayoutMutationResultData $result): void
    {
        $this->layoutUndoSnapshots[] = $this->layoutState()->toLivewirePayload();
        $this->layoutRedoSnapshots = [];

        $this->applyLayoutState($result->state, markModified: true);
        $this->layoutDiagnostics = array_map(
            fn (mixed $diagnostic): array => method_exists($diagnostic, 'toArray') ? $diagnostic->toArray() : (array) $diagnostic,
            $result->diagnostics,
        );
        $this->layoutChanges = array_map(
            fn (mixed $change): array => method_exists($change, 'toArray') ? $change->toArray() : (array) $change,
            $result->changes,
        );
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();
    }

    protected function applyLayoutState(LayoutBuilderStateData $state, bool $markModified): void
    {
        $this->containers = $state->containers;
        $this->assets = $state->assets;
        $this->originalAssets = $state->originalAssets;
        $this->selectedRecords = $state->selectedRecords;

        $this->rebuildLoadedContainerElements();

        $this->layoutUpdated($markModified);
    }

    protected function rebuildLoadedContainerElements(): void
    {
        $this->containerElements = [];
        $elementKeys = collect($this->containers ?? [])
            ->flatMap(fn (array $container): array => array_map(
                static fn (array $element): mixed => $element['element_key'] ?? null,
                $container['elements'] ?? [],
            ))
            ->filter(static fn (mixed $elementKey): bool => is_string($elementKey) && $elementKey !== '')
            ->unique()
            ->values()
            ->all();

        $elementsByKey = $elementKeys === []
            ? collect()
            : $this->getElementDisplayQuery()
                ->whereIn('key', $elementKeys)
                ->get()
                ->keyBy('key');

        foreach ($this->containers ?? [] as $containerKey => $container) {
            foreach (($container['elements'] ?? []) as $elementIndex => $element) {
                $elementKey = $element['element_key'] ?? null;

                if (! is_string($elementKey)) {
                    continue;
                }

                $loadedElement = $elementsByKey->get($elementKey);

                if ($loadedElement !== null) {
                    $this->containerElements[$containerKey][$elementIndex] = $loadedElement;
                }
            }
        }
    }

    protected function layoutState(): LayoutBuilderStateData
    {
        return LayoutBuilderStateData::fromLivewire(
            containers: $this->containers,
            assets: $this->assets,
            originalAssets: $this->originalAssets,
            selectedRecords: $this->selectedRecords,
        );
    }

    protected function stateFromSnapshot(array $snapshot): LayoutBuilderStateData
    {
        return new LayoutBuilderStateData(
            containers: $snapshot['containers'] ?? [],
            assets: $snapshot['assets'] ?? [],
            originalAssets: $snapshot['originalAssets'] ?? [],
            selectedRecords: $snapshot['selectedRecords'] ?? [],
        );
    }

    protected function captureSavedBaselineState(): void
    {
        $this->savedBaselineSnapshot = $this->layoutState()->toLivewirePayload();
    }

    protected function savedBaselineState(): LayoutBuilderStateData
    {
        if ($this->savedBaselineSnapshot === null) {
            $this->captureSavedBaselineState();
        }

        return $this->stateFromSnapshot($this->savedBaselineSnapshot ?? []);
    }

    protected function refreshLayoutChanges(): void
    {
        $this->layoutChanges = array_map(
            fn (mixed $change): array => method_exists($change, 'toArray') ? $change->toArray() : (array) $change,
            SummarizeLayoutChangesAction::run($this->savedBaselineState(), $this->layoutState()),
        );
    }

    protected function refreshLayoutDiagnostics(): void
    {
        $this->layoutDiagnostics = array_map(
            fn (mixed $diagnostic): array => method_exists($diagnostic, 'toArray') ? $diagnostic->toArray() : (array) $diagnostic,
            AnalyzeLayoutDiagnosticsAction::run($this->layoutState()),
        );
    }

    protected function hasBlockingLayoutDiagnostics(): bool
    {
        foreach ($this->layoutDiagnostics as $diagnostic) {
            $severity = data_get($diagnostic, 'severity');

            if ($severity instanceof LayoutDiagnosticSeverity) {
                if ($severity === LayoutDiagnosticSeverity::Blocking) {
                    return true;
                }

                continue;
            }

            if ($severity === LayoutDiagnosticSeverity::Blocking->value) {
                return true;
            }
        }

        return false;
    }

    protected function getSite(): ?Site
    {
        if ($this->site instanceof Site) {
            return $this->site;
        }

        if (! $this->inPageContext()) {
            return null;
        }

        return $this->page->site;
    }

    /**
     * @return array<string, array<int, array<int, array<string, mixed>>>>
     */
    private function contentInventorySignatureAssets(): array
    {
        $assets = [];

        foreach ($this->assets as $containerKey => $containerAssets) {
            foreach ($containerAssets as $elementIndex => $elementAssets) {
                foreach ($elementAssets as $assetIndex => $asset) {
                    $assets[$containerKey][$elementIndex][$assetIndex] = [
                        'id' => $asset['id'] ?? null,
                        'layout_element_id' => $asset['layout_element_id'] ?? null,
                        'asset_id' => $asset['asset_id'] ?? null,
                        'asset_type' => $asset['asset_type'] ?? null,
                        'order' => $asset['order'] ?? null,
                        'occurrence' => $asset['occurrence'] ?? null,
                        'pageable_id' => $asset['pageable_id'] ?? null,
                        'pageable_type' => $asset['pageable_type'] ?? null,
                        'container' => $asset['container'] ?? null,
                    ];
                }
            }
        }

        return $assets;
    }

    private function resolveInitialEditorMode(): string
    {
        $configuredMode = LayoutBuilderEditorMode::fromConfig(
            $this->configuredDefaultEditorMode(),
        );

        if ($configuredMode === LayoutBuilderEditorMode::ContentFirst && $this->canEditContent()) {
            return $configuredMode->value;
        }

        if ($configuredMode === LayoutBuilderEditorMode::LayoutFirst && $this->canEditLayout()) {
            return $configuredMode->value;
        }

        if ($this->canEditContent()) {
            return LayoutBuilderEditorMode::ContentFirst->value;
        }

        if ($this->canEditLayout()) {
            return LayoutBuilderEditorMode::LayoutFirst->value;
        }

        return $configuredMode->value;
    }

    private function configuredDefaultEditorMode(): string
    {
        $configuration = LayoutBuilderConfiguration::class;

        if (class_exists($configuration) && method_exists($configuration, 'defaultEditorMode')) {
            return $configuration::defaultEditorMode();
        }

        $packageMode = config('capell-layout-builder.editor_mode.default');

        if (is_string($packageMode) && $packageMode !== '') {
            return $packageMode;
        }

        $legacyPackageMode = config('capell-layout-builder.editor_mode');

        if (is_string($legacyPackageMode) && $legacyPackageMode !== '') {
            return $legacyPackageMode;
        }

        $adminMode = config('capell-admin.layout_builder.default_editor_mode');

        return is_string($adminMode) && $adminMode !== ''
            ? $adminMode
            : LayoutBuilderEditorMode::ContentFirst->value;
    }

    private function canPerformLayoutBuilderAbility(string $ability): bool
    {
        $actor = Filament::auth()->user();

        if ($actor === null) {
            return false;
        }

        if ($ability === 'editLayout' && $this->page instanceof Model) {
            return $this->allowsLayoutBuilderAbility($actor, $ability, $this->page)
                && $this->allowsLayoutBuilderAbility($actor, $ability, $this->layout);
        }

        $record = $this->page instanceof Model ? $this->page : $this->layout;

        return $this->allowsLayoutBuilderAbility($actor, $ability, $record);
    }

    private function allowsLayoutBuilderAbility(Authenticatable $actor, string $ability, Model $record): bool
    {
        $policy = Gate::getPolicyFor($record);
        $resolvedAbility = $policy !== null && method_exists($policy, $ability) ? $ability : 'update';

        return Gate::forUser($actor)->allows($resolvedAbility, $record);
    }

    private function authorizeLayoutBuilderAbility(Authenticatable $actor, string $ability, Model $record): void
    {
        $policy = Gate::getPolicyFor($record);
        $resolvedAbility = $policy !== null && method_exists($policy, $ability) ? $ability : 'update';

        Gate::forUser($actor)->authorize($resolvedAbility, $record);
    }

    /**
     * @param  array<int, string>  $knownContainerKeys
     */
    private function trackNewContainerKeysSince(array $knownContainerKeys): void
    {
        foreach (array_diff(array_keys($this->containers ?? []), $knownContainerKeys) as $containerKey) {
            $this->trackKnownContainerKey($containerKey);
        }
    }

    private function assertValidContainerKey(string $containerKey): void
    {
        $this->ensureLoaded();

        if (array_key_exists($containerKey, $this->containers) && in_array($containerKey, $this->knownContainerKeys, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'containerKey' => __('capell-layout-builder::message.no_container_selected'),
        ]);
    }

    private function assertKnownContainerStructure(): void
    {
        $this->ensureLoaded();

        foreach (array_keys($this->containers) as $containerKey) {
            if (! is_string($containerKey) || $containerKey === '' || preg_match('/^[A-Za-z0-9_-]+$/', $containerKey) !== 1) {
                throw ValidationException::withMessages([
                    'containers' => __('capell-layout-builder::message.invalid_layout_containers'),
                ]);
            }

            if (! in_array($containerKey, $this->knownContainerKeys, true)) {
                throw ValidationException::withMessages([
                    'containers' => __('capell-layout-builder::message.invalid_layout_containers'),
                ]);
            }
        }
    }
}
