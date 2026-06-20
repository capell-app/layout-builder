<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\FilamentPeek\Actions\StoreLayoutBuilderPreviewStateAction;
use Capell\FilamentPeek\Contracts\StoresLayoutBuilderPreviewState;
use Capell\LayoutBuilder\Actions\AnalyzeLayoutHealthAction;
use Capell\LayoutBuilder\Actions\BuildLayoutContentInventoryAction;
use Capell\LayoutBuilder\Actions\Mutations\PushLayoutMutationSnapshotAction;
use Capell\LayoutBuilder\Actions\SummarizeLayoutChangesAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutClipboard;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use LogicException;

trait ManagesLayoutBuilderState
{
    #[Computed]
    public function contentInventory(): LayoutContentInventoryData
    {
        $this->ensureLoaded();

        return BuildLayoutContentInventoryAction::run(
            layout: $this->layout,
            page: $this->page,
            containers: $this->containers ?? [],
            containerWidgets: $this->containerWidgets ?? [],
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

    /**
     * @phpstan-assert array<array-key, mixed> $this->containers
     */
    public function ensureLoaded(): void
    {
        if (! isset($this->containerWidgets)) {
            $this->loadFromStore();
        }
    }

    public function loadFromStore(): void
    {
        $this->setupContainers();

        $widgets = $this->preloadAllWidgets(withAssets: false);

        $allWidgetAssets = $this->preloadAllWidgetAssets();

        $containerWidgetAssets = [];

        foreach ($this->assets as $containerKey => $containerWidgets) {
            foreach ($containerWidgets as $widgetIndex => $widgetAssets) {
                $widgetKey = $this->containers[$containerKey]['widgets'][$widgetIndex]['widget_key'] ?? null;

                if ($widgetKey === null) {
                    continue;
                }

                /** @var Widget $widget */
                $widget = $widgets[$widgetKey];

                $containerWidgetAssets[$containerKey][$widgetIndex] = $this->setupWidgetAssets(
                    $containerKey,
                    $widgetIndex,
                    $widgetAssets,
                    $allWidgetAssets,
                    $widget,
                );
            }
        }

        foreach (array_keys($this->containers ?? []) as $containerKey) {
            $this->setupContainerWidgets($containerKey, $widgets, $containerWidgetAssets);
        }
    }

    /**
     * @phpstan-assert-if-true Pageable $this->page
     */
    public function inPageContext(): bool
    {
        return $this->page instanceof Pageable;
    }

    public function pageContext(): Pageable
    {
        throw_unless($this->page instanceof Pageable, LogicException::class, 'Layout builder page context is not available.');

        return $this->page;
    }

    public function layoutUpdated(bool $modified = true): void
    {
        $wasModified = $this->layoutModified;

        $this->layoutModified = $modified;

        $this->visualPreviewStatus = $modified ? 'stale' : 'current';

        if ($modified) {
            $this->dispatch('capell-layout-builder-authoring-dirty');

            if (! $wasModified) {
                Notification::make('layout-unsaved')
                    ->title(__('capell-layout-builder::message.layout_unsaved'))
                    ->warning()
                    ->send();
            }

            $this->refreshVisualPreview();
        }

        if (! $this->inPageContext()) {
            return;
        }

        $actionClass = StoreLayoutBuilderPreviewStateAction::class;

        if (! class_exists($actionClass)) {
            return;
        }

        $action = resolve($actionClass);

        if (! $action instanceof StoresLayoutBuilderPreviewState) {
            return;
        }

        if (! $modified) {
            $action->clear($this->page);

            return;
        }

        $action->handle(
            page: $this->page,
            layout: $this->layout,
            containers: $this->containers ?? [],
            assets: $this->assets,
        );
    }

    public function getSite(): ?Site
    {
        if ($this->site instanceof Site) {
            return $this->site;
        }

        if (! $this->inPageContext()) {
            return null;
        }

        return $this->pageContext()->site;
    }

    protected function persistWidgetAssets(): void
    {
        $processedWidgetKeys = [];

        foreach (array_keys($this->containers ?? []) as $containerKey) {
            foreach ($this->containerWidgets((string) $containerKey) as $widgetIndex => $widget) {
                if ($this->inPageContext() && isset($widget['pageable_type'], $widget['pageable_id'])) {
                    $key = $widget['widget_key'] . '_' . $widget['pageable_type'] . '_' . $widget['pageable_id'] . '_' . $widget['container'] . '_' . $widget['occurrence'];
                } else {
                    $key = $widget['widget_key'] . '_' . $widget['occurrence'];
                }

                if (in_array($key, $processedWidgetKeys, true)) {
                    continue;
                }

                $processedWidgetKeys[] = $key;

                $this->updateAssets($containerKey, $widgetIndex, $widget['old_container'] ?? null);
            }
        }

        if ($this->inPageContext()) {
            $this->deleteRemovedWidgetAssets();
        }
    }

    protected function clipboard(): LayoutClipboard
    {
        return $this->layoutClipboard ??= new LayoutClipboard;
    }

    protected function loadNew(): void
    {
        $this->setupContainers();

        $widgets = $this->preloadAllWidgets();

        foreach (array_keys($this->containers ?? []) as $containerKey) {
            $this->setupContainerWidgets($containerKey, $widgets);
        }

        $this->setupSelectedAssets();

        $this->saveOriginalAssets();
        $this->captureSavedBaselineState();
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();
    }

    protected function reload(): void
    {
        $this->reset('containerWidgets', 'selectedRecords', 'assets', 'originalAssets', 'containers', 'layout');

        $this->loadNew();
    }

    protected function applyLayoutMutationResult(LayoutMutationResultData $result): void
    {
        $history = PushLayoutMutationSnapshotAction::run($this->layoutState(), $this->layoutUndoSnapshots);
        $this->layoutUndoSnapshots = $history->undoSnapshots;
        $this->layoutRedoSnapshots = $history->redoSnapshots;

        $this->applyLayoutState($result->state, markModified: true);
        $this->layoutDiagnostics = array_map(
            fn (LayoutDiagnosticData $diagnostic): array => $diagnostic->toArray(),
            $result->diagnostics,
        );
        $this->layoutChanges = array_map(
            fn (mixed $change): array => $change->toArray(),
            $result->changes,
        );
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();
    }

    protected function applyLayoutState(LayoutBuilderStateData $state, bool $markModified): void
    {
        $normalizedContainers = [];

        foreach ($state->containers as $containerKey => $container) {
            $normalizedContainers[(string) $containerKey] = is_array($container) ? $container : [];
        }

        $this->containers = $normalizedContainers;
        $this->assets = $state->assets;
        $this->originalAssets = $state->originalAssets;
        $this->selectedRecords = $state->selectedRecords;

        $this->rebuildLoadedContainerWidgets();

        $this->layoutUpdated($markModified);
    }

    protected function rebuildLoadedContainerWidgets(): void
    {
        $this->containerWidgets = [];
        $widgetKeys = collect($this->containers ?? [])
            ->flatMap(function (array $container): array {
                $widgets = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];

                return array_map(
                    static fn (mixed $widget): mixed => is_array($widget) ? ($widget['widget_key'] ?? null) : null,
                    $widgets,
                );
            })
            ->filter(static fn (mixed $widgetKey): bool => is_string($widgetKey) && $widgetKey !== '')
            ->unique()
            ->values()
            ->all();

        $widgetsByKey = $widgetKeys === []
            ? collect()
            : $this->getWidgetDisplayQuery()
                ->whereIn('key', $widgetKeys)
                ->get()
                ->keyBy('key');

        foreach ($this->containers ?? [] as $containerKey => $container) {
            foreach (($container['widgets'] ?? []) as $widgetIndex => $widget) {
                $widgetKey = $widget['widget_key'] ?? null;

                if (! is_string($widgetKey)) {
                    continue;
                }

                $loadedWidget = $widgetsByKey->get($widgetKey);

                if ($loadedWidget !== null) {
                    $this->containerWidgets[$containerKey][$widgetIndex] = $loadedWidget;
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

    /**
     * @param  array<array-key, mixed>  $snapshot
     */
    protected function stateFromSnapshot(array $snapshot): LayoutBuilderStateData
    {
        return LayoutBuilderStateData::fromSnapshot($snapshot);
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
        $changes = SummarizeLayoutChangesAction::run($this->savedBaselineState(), $this->layoutState());

        $this->layoutChanges = array_map(
            fn (mixed $change): array => is_object($change) && method_exists($change, 'toArray') ? $change->toArray() : (array) $change,
            is_array($changes) ? $changes : [],
        );
    }

    protected function refreshLayoutDiagnostics(): void
    {
        $this->layoutDiagnostics = array_map(
            fn (mixed $diagnostic): array => $diagnostic->toArray(),
            AnalyzeLayoutHealthAction::run($this->layoutState(), $this->activeThemeKey()),
        );
    }

    protected function activeThemeKey(): ?string
    {
        $layoutTheme = $this->layout->relationLoaded('theme') ? $this->layout->getRelation('theme') : $this->layout->theme()->first();

        if ($layoutTheme instanceof Theme) {
            return $layoutTheme->key;
        }

        $site = $this->getSite();
        $siteTheme = $site?->relationLoaded('theme') === true ? $site->getRelation('theme') : $site?->theme()->first();

        return $siteTheme instanceof Theme ? $siteTheme->key : null;
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

    /**
     * @return array<string, array<int, array<int, array<array-key, mixed>>>>
     */
    private function contentInventorySignatureAssets(): array
    {
        $assets = [];

        foreach ($this->assets as $containerKey => $containerAssets) {
            foreach ($containerAssets as $widgetIndex => $widgetAssets) {
                foreach ($widgetAssets as $assetIndex => $assetEntry) {
                    $asset = is_array($assetEntry) ? $assetEntry : [];

                    $assets[$containerKey][$widgetIndex][$assetIndex] = [
                        'id' => $asset['id'] ?? null,
                        'widget_id' => $asset['widget_id'] ?? null,
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
