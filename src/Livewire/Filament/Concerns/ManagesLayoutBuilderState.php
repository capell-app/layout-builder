<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\FilamentPeek\Actions\StoreLayoutBuilderPreviewStateAction;
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
            containerBlocks: $this->containerBlocks ?? [],
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
        if (! isset($this->containerBlocks)) {
            $this->loadFromStore();
        }
    }

    public function loadFromStore(): void
    {
        $this->setupContainers();

        $blocks = $this->preloadAllBlocks(withAssets: false);

        $allBlockAssets = $this->preloadAllBlockAssets();

        $containerBlockAssets = [];

        foreach ($this->assets as $containerKey => $containerBlocks) {
            foreach ($containerBlocks as $blockIndex => $blockAssets) {
                $widgetKey = $this->containers[$containerKey]['widgets'][$blockIndex]['widget_key'] ?? null;

                if ($widgetKey === null) {
                    continue;
                }

                /** @var Widget $block */
                $block = $blocks[$widgetKey];

                $containerBlockAssets[$containerKey][$blockIndex] = $this->setupBlockAssets(
                    $containerKey,
                    $blockIndex,
                    $blockAssets,
                    $allBlockAssets,
                    $block,
                );
            }
        }

        foreach (array_keys($this->containers ?? []) as $containerKey) {
            $this->setupContainerBlocks($containerKey, $blocks, $containerBlockAssets);
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
        $this->layoutModified = $modified;

        $this->visualPreviewStatus = $modified ? 'stale' : 'current';

        if ($modified) {
            $this->dispatch('capell-layout-builder-authoring-dirty');
        }

        if (! $this->inPageContext()) {
            return;
        }

        $actionClass = StoreLayoutBuilderPreviewStateAction::class;

        if (! class_exists($actionClass)) {
            return;
        }

        $action = resolve($actionClass);

        if (! $modified) {
            if (method_exists($action, 'clear')) {
                $action->clear($this->page);
            }

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

    protected function persistBlockAssets(): void
    {
        $processedWidgetKeys = [];

        foreach (array_keys($this->containers ?? []) as $containerKey) {
            foreach ($this->containerWidgets((string) $containerKey) as $blockIndex => $block) {
                if ($this->inPageContext() && isset($block['pageable_type'], $block['pageable_id'])) {
                    $key = $block['widget_key'] . '_' . $block['pageable_type'] . '_' . $block['pageable_id'] . '_' . $block['container'] . '_' . $block['occurrence'];
                } else {
                    $key = $block['widget_key'] . '_' . $block['occurrence'];
                }

                if (in_array($key, $processedWidgetKeys, true)) {
                    continue;
                }

                $processedWidgetKeys[] = $key;

                $this->updateAssets($containerKey, $blockIndex, $block['old_container'] ?? null);
            }
        }

        if ($this->inPageContext()) {
            $this->deleteRemovedBlockAssets();
        }
    }

    protected function clipboard(): LayoutClipboard
    {
        return $this->layoutClipboard ??= new LayoutClipboard;
    }

    protected function loadNew(): void
    {
        $this->setupContainers();

        $blocks = $this->preloadAllBlocks();

        foreach (array_keys($this->containers ?? []) as $containerKey) {
            $this->setupContainerBlocks($containerKey, $blocks);
        }

        $this->setupSelectedAssets();

        $this->saveOriginalAssets();
        $this->captureSavedBaselineState();
        $this->refreshLayoutChanges();
        $this->refreshLayoutDiagnostics();
    }

    protected function reload(): void
    {
        $this->reset('containerBlocks', 'selectedRecords', 'assets', 'originalAssets', 'containers', 'layout');

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
        $this->containers = $state->containers;
        $this->assets = $state->assets;
        $this->originalAssets = $state->originalAssets;
        $this->selectedRecords = $state->selectedRecords;

        $this->rebuildLoadedContainerBlocks();

        $this->layoutUpdated($markModified);
    }

    protected function rebuildLoadedContainerBlocks(): void
    {
        $this->containerBlocks = [];
        $widgetKeys = collect($this->containers ?? [])
            ->flatMap(fn (array $container): array => array_map(
                static fn (array $block): mixed => $block['widget_key'] ?? null,
                $container['widgets'] ?? [],
            ))
            ->filter(static fn (mixed $widgetKey): bool => is_string($widgetKey) && $widgetKey !== '')
            ->unique()
            ->values()
            ->all();

        $blocksByKey = $widgetKeys === []
            ? collect()
            : $this->getBlockDisplayQuery()
                ->whereIn('key', $widgetKeys)
                ->get()
                ->keyBy('key');

        foreach ($this->containers ?? [] as $containerKey => $container) {
            foreach (($container['widgets'] ?? []) as $blockIndex => $block) {
                $widgetKey = $block['widget_key'] ?? null;

                if (! is_string($widgetKey)) {
                    continue;
                }

                $loadedBlock = $blocksByKey->get($widgetKey);

                if ($loadedBlock !== null) {
                    $this->containerBlocks[$containerKey][$blockIndex] = $loadedBlock;
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
        $this->layoutChanges = array_map(
            fn (mixed $change): array => is_object($change) && method_exists($change, 'toArray') ? $change->toArray() : (array) $change,
            SummarizeLayoutChangesAction::run($this->savedBaselineState(), $this->layoutState()),
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
            foreach ($containerAssets as $blockIndex => $blockAssets) {
                foreach ($blockAssets as $assetIndex => $asset) {
                    $assets[$containerKey][$blockIndex][$assetIndex] = [
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
