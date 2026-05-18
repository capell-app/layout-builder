<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\LayoutBuilder\Actions\AnalyzeLayoutHealthAction;
use Capell\LayoutBuilder\Actions\BuildLayoutContentInventoryAction;
use Capell\LayoutBuilder\Actions\Mutations\PushLayoutMutationSnapshotAction;
use Capell\LayoutBuilder\Actions\SummarizeLayoutChangesAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryData;
use Capell\LayoutBuilder\Data\LayoutMutationResultData;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Support\LayoutClipboard;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

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

    protected function layoutUpdated(bool $modified = true): void
    {
        $this->layoutModified = $modified;
    }

    protected function applyLayoutMutationResult(LayoutMutationResultData $result): void
    {
        $history = PushLayoutMutationSnapshotAction::run($this->layoutState(), $this->layoutUndoSnapshots);
        $this->layoutUndoSnapshots = $history->undoSnapshots;
        $this->layoutRedoSnapshots = $history->redoSnapshots;

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
            fn (mixed $change): array => method_exists($change, 'toArray') ? $change->toArray() : (array) $change,
            SummarizeLayoutChangesAction::run($this->savedBaselineState(), $this->layoutState()),
        );
    }

    protected function refreshLayoutDiagnostics(): void
    {
        $this->layoutDiagnostics = array_map(
            fn (mixed $diagnostic): array => method_exists($diagnostic, 'toArray') ? $diagnostic->toArray() : (array) $diagnostic,
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
