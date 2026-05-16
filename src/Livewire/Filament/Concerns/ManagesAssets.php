<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use BackedEnum;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

trait ManagesAssets
{
    public function reorderAssets(string $containerKey, int $elementIndex, int $index, int $newIndex): void
    {
        $this->assertCanUpdateLayout();

        $assets = $this->assets[$containerKey][$elementIndex];

        $elementAsset = $this->getElementAsset($containerKey, $elementIndex, $index);

        throw_if($elementAsset === null || $elementAsset === [], Exception::class, sprintf('Asset %d not found for container: %s element: %d', $index, $containerKey, $elementIndex));

        unset($assets[$index]);

        $assets = array_values($assets);

        array_splice($assets, $newIndex, 0, [$elementAsset]);

        $order = 1;
        $assets = array_map(
            function (array $asset) use (&$order): array {
                $asset['order'] = $order;
                $order++;

                return $asset;
            },
            $assets,
        );

        $this->assets[$containerKey][$elementIndex] = $assets;

        $this->layoutUpdated();
    }

    public function moveAssetUp(string $containerKey, int $elementIndex, int $assetIndex): void
    {
        if (! $this->canMoveAssetUp($containerKey, $elementIndex, $assetIndex)) {
            return;
        }

        $this->reorderAssets($containerKey, $elementIndex, $assetIndex, $assetIndex - 1);
    }

    public function moveAssetDown(string $containerKey, int $elementIndex, int $assetIndex): void
    {
        if (! $this->canMoveAssetDown($containerKey, $elementIndex, $assetIndex)) {
            return;
        }

        $this->reorderAssets($containerKey, $elementIndex, $assetIndex, $assetIndex + 1);
    }

    public function canMoveAssetUp(string $containerKey, int $elementIndex, int $assetIndex): bool
    {
        return $assetIndex > 0 && isset($this->assets[$containerKey][$elementIndex][$assetIndex]);
    }

    public function canMoveAssetDown(string $containerKey, int $elementIndex, int $assetIndex): bool
    {
        return isset($this->assets[$containerKey][$elementIndex][$assetIndex + 1]);
    }

    public function hasPageAssets(string $containerKey, int $elementIndex): bool
    {
        if (! $this->inPageContext()) {
            return false;
        }

        $assets = $this->getElementAssets($containerKey, $elementIndex);

        if ($assets === []) {
            return false;
        }

        return collect($assets)
            ->contains(
                fn (array $asset): bool => isset($asset['pageable_type'], $asset['pageable_id'])
                    && $asset['pageable_type'] === $this->page->getMorphClass()
                    && $asset['pageable_id'] === $this->page->getKey(),
            );
    }

    public function elementHasPageAssets(Element $element): bool
    {
        if (! $this->inPageContext()) {
            return $element->assets()->whereNotNull('pageable_type')->whereNotNull('pageable_id')->exists();
        }

        if (property_exists($element, 'page_assets_count')) {
            return $element->page_assets_count > 0;
        }

        return $element
            ->assets()
            ->where([
                'pageable_type' => $this->page->getMorphClass(),
                'pageable_id' => $this->page->getKey(),
            ])
            ->exists();
    }

    public function elementHasGlobalAssets(Element $element): bool
    {
        if (property_exists($element, 'global_assets_count')) {
            return $element->global_assets_count > 0;
        }

        return $element->assets()->whereNull(['pageable_type', 'pageable_id'])->exists();
    }

    public function selectAllAssets(string $containerKey, int $elementIndex): void
    {
        $this->assertCanUpdateLayout();

        $this->selectedRecords[$containerKey][$elementIndex] = $this->getAllSelectableAssetsKeys(
            $containerKey,
            $elementIndex,
        );
    }

    public function deSelectAllAssets(string $containerKey, int $elementIndex): void
    {
        $this->assertCanUpdateLayout();

        $this->selectedRecords[$containerKey][$elementIndex] = [];
    }

    public function getElementAssetTypes(Element $element): array
    {
        return $this->getAllowedAssetTypes($element);
    }

    protected function moveContainerElementAssets(string $originalContainer, int $originalIndex, string $containerKey, int $elementIndex): void
    {
        $element = $this->assets[$originalContainer][$originalIndex];
        $elementSelectedRecords = $this->selectedRecords[$originalContainer][$originalIndex] ?? [];

        $assets = $this->assets[$containerKey] ?? [];
        $assets = array_merge(array_slice($assets, 0, $elementIndex), [$element], array_slice($assets, $elementIndex));
        $this->assets[$containerKey] = $assets;

        if ($containerKey !== $originalContainer) {
            unset($this->assets[$originalContainer][$originalIndex]);
            $this->assets[$originalContainer] = array_values($this->assets[$originalContainer]);
        }

        $selectedRecords = $this->selectedRecords[$containerKey] ?? [];
        $selectedRecords = array_merge(array_slice($selectedRecords, 0, $elementIndex), [$elementSelectedRecords], array_slice($selectedRecords, $elementIndex));
        $this->selectedRecords[$containerKey] = $selectedRecords;

        if ($containerKey !== $originalContainer && isset($this->selectedRecords[$originalContainer][$originalIndex])) {
            unset($this->selectedRecords[$originalContainer][$originalIndex]);
            $this->selectedRecords[$originalContainer] = array_values($this->selectedRecords[$originalContainer]);
        }
    }

    protected function updatePageAssets(string $containerKey, int $elementIndex, ?bool $hasPageAssets = null): void
    {
        if (! $this->assets[$containerKey][$elementIndex]) {
            return;
        }

        if ($hasPageAssets === null) {
            $hasPageAssets = $this->hasPageAssets($containerKey, $elementIndex);
        }

        foreach ($this->assets[$containerKey][$elementIndex] as $assetIndex => $asset) {
            if ($hasPageAssets) {
                $this->assets[$containerKey][$elementIndex][$assetIndex]['pageable_id'] = $this->page->getKey();
                $this->assets[$containerKey][$elementIndex][$assetIndex]['pageable_type'] = $this->page->getMorphClass();
            } else {
                $this->assets[$containerKey][$elementIndex][$assetIndex]['pageable_id'] = null;
                $this->assets[$containerKey][$elementIndex][$assetIndex]['pageable_type'] = null;
            }
        }
    }

    protected function mapElementAssets(Element $element, string $containerKey, ?string $oldContainerKey = null): array
    {
        return $element->assets->map(
            static function (ElementAsset $elementAsset) use ($containerKey, $oldContainerKey): array {
                $asset = [
                    'id' => $elementAsset->id,
                    'layout_element_id' => $elementAsset->layout_element_id,
                    'workspace_id' => $elementAsset->workspace_id,
                    'asset_id' => $elementAsset->asset_id,
                    'asset_type' => $elementAsset->asset_type,
                    'meta' => $elementAsset->meta,
                    'order' => $elementAsset->order,
                    'occurrence' => $elementAsset->occurrence,
                ];

                if ($elementAsset->pageable_id !== null && $elementAsset->pageable_type !== null) {
                    $asset['pageable_id'] = $elementAsset->pageable_id;
                    $asset['pageable_type'] = $elementAsset->pageable_type;
                    $asset['container'] = $containerKey;
                }

                if ($oldContainerKey !== null && $oldContainerKey !== '') {
                    $asset['old_container'] = $oldContainerKey;
                }

                return $asset;
            },
        )->all();
    }

    protected function setupElementAssets(string $containerKey, int $elementIndex, array $elementAssets, ?Collection $allElementAssets, Element $element): Collection
    {
        $assets = new Collection;

        if (! $allElementAssets instanceof Collection || $allElementAssets->isEmpty()) {
            return $assets;
        }

        /** @var Collection<int, ElementAsset> $allElementAssets */
        $occurrence = $this->getContainerElementOccurrence($containerKey, $elementIndex);

        foreach ($elementAssets as $elementAssetData) {
            $type = $elementAssetData['asset_type'];
            $assetId = is_numeric($elementAssetData['asset_id']) ? (int) $elementAssetData['asset_id'] : $elementAssetData['asset_id'];

            $oldContainerKey = $elementAssetData['old_container'] ?? $containerKey;

            /** @var ?ElementAsset $matchingAsset */
            $matchingAsset = isset($elementAssetData['id'])
                ? $allElementAssets->first(fn (ElementAsset $asset): bool => $asset->getKey() === (int) $elementAssetData['id']
                    && $this->elementAssetMatchesState($asset, $elementAssetData, $containerKey, $oldContainerKey, $occurrence, $element))
                : null;

            $matchingAsset ??= $allElementAssets->first(function (ElementAsset $asset) use ($type, $assetId, $oldContainerKey, $occurrence, $element): bool {
                if ((int) $asset->layout_element_id !== (int) $element->getKey()) {
                    return false;
                }

                if (! in_array($asset->workspace_id, $this->getReadableElementAssetWorkspaceIds($element), true)) {
                    return false;
                }

                $matchesElement = $asset->asset_type === $type
                    && $asset->asset_id === $assetId;

                if (! $matchesElement) {
                    return false;
                }

                $matchesOccurrence = (int) $asset->occurrence === $occurrence;

                if (! $matchesOccurrence) {
                    return false;
                }

                if (! $this->inPageContext()) {
                    return $asset->pageable_type === null || $asset->pageable_id === null;
                }

                $matchesPage = $asset->pageable_type === $this->page->getMorphClass()
                    && $asset->pageable_id === $this->page->getKey();

                $matchesContainer = $asset->container === null || $asset->container === $oldContainerKey;

                return $matchesPage && $matchesContainer;
            });

            if ($matchingAsset === null) {
                continue;
            }

            $elementAsset = clone $matchingAsset;
            $elementAsset->order = $elementAssetData['order'] ?? $elementAsset->order;
            $elementAsset->occurrence = $elementAssetData['occurrence'] ?? $occurrence;
            $elementAsset->pageable_id = $elementAssetData['pageable_id'] ?? null;
            $elementAsset->pageable_type = $elementAssetData['pageable_type'] ?? null;

            $assets->push($elementAsset);
        }

        return $assets;
    }

    protected function setupSelectedAssets(): void
    {
        $this->selectedRecords = [];

        foreach ($this->containers as $containerKey => $container) {
            $this->selectedRecords[$containerKey] = [];

            foreach ($container['elements'] as $elementIndex => $element) {
                $this->selectedRecords[$containerKey][$elementIndex] = [];
            }
        }
    }

    protected function saveOriginalAssets(): void
    {
        $originalAssets = [];

        foreach ($this->assets as $containerKey => $containerElements) {
            foreach ($containerElements as $elementIndex => $elementAssets) {
                $containerElement = $this->getContainerElement($containerKey, $elementIndex);

                foreach ($elementAssets as $elementAssetIndex => $elementAsset) {
                    $elementAsset['original_container_key'] = $containerKey;
                    $elementAsset['original_element_id'] = $containerElement->id;
                    $elementAsset['original_element_key'] = $containerElement->key;

                    $originalAssets[$containerKey][$elementIndex][$elementAssetIndex] = $elementAsset;
                }
            }
        }

        $this->originalAssets = $originalAssets;
    }

    protected function getSelectedAssets(string $containerKey, int $elementIndex): array
    {
        return $this->selectedRecords[$containerKey][$elementIndex] ?? [];
    }

    protected function getAllSelectableAssetsKeys(string $containerKey, int $elementIndex): array
    {
        return collect($this->assets[$containerKey][$elementIndex])
            ->map(fn (array $elementAsset): string => sprintf('%s.%s', $elementAsset['asset_type'], $elementAsset['asset_id']))
            ->values()
            ->all();
    }

    protected function addAssets(string $containerKey, int $elementIndex, ?bool $hasPageAssets, string $type, mixed $assets, array $assetsMeta = []): void
    {
        $this->assertCanUpdateLayout();

        if (! isset($this->assets[$containerKey][$elementIndex])) {
            $this->assets[$containerKey][$elementIndex] = [];
        }

        $element = $this->getContainerElement($containerKey, $elementIndex);

        $validatedAssetIds = $this->getValidatedAssetIds($element, $type, $assets);

        if ($validatedAssetIds === []) {
            return;
        }

        $occurrence = $this->getContainerElementOccurrence($containerKey, $elementIndex);

        $order = $this->countElementAssets($containerKey, $elementIndex);

        foreach ($validatedAssetIds as $assetId) {
            $order++;

            $meta = $assetsMeta[$assetId] ?? [];

            $asset = [
                'asset_id' => $assetId,
                'asset_type' => $type,
                'meta' => $meta,
                'layout_element_id' => $element->id,
                'order' => $order,
                'occurrence' => $occurrence,
            ];

            if ($hasPageAssets === true) {
                $asset['pageable_id'] = $this->page->getKey();
                $asset['pageable_type'] = $this->page->getMorphClass();
                $asset['container'] = $containerKey;
            }

            $this->assets[$containerKey][$elementIndex][] = $asset;

            $elementAsset = $this->addElementAsset(
                element: $element,
                containerKey: $containerKey,
                type: $type,
                hasPageAssets: $hasPageAssets,
                assetId: $assetId,
                meta: $meta,
                occurrence: $occurrence,
                order: $order,
            );

            $elementAsset->setRelation('element', $element);

            $element->assets->add($elementAsset);
        }

        $element->assets->load([
            'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
        ]);

        $this->containerElements[$containerKey][$elementIndex] = $element;
    }

    protected function getValidatedAssetIds(Element $element, string $type, mixed $assetIds): array
    {
        $normalizedType = $this->normalizeAssetType($type);

        if (! in_array($normalizedType, $this->getAllowedAssetTypes($element), true)) {
            return [];
        }

        $registeredType = Str::ucfirst($normalizedType);

        if (! CapellCore::hasAsset($registeredType)) {
            return [];
        }

        $assetData = CapellCore::getAsset($registeredType);
        $model = $assetData->model;

        if (! is_subclass_of($model, Model::class)) {
            return [];
        }

        $requestedAssetIds = collect(Arr::wrap($assetIds))
            ->filter(fn (mixed $assetId): bool => (is_int($assetId) || is_string($assetId)) && $assetId !== '')
            ->values();

        if ($requestedAssetIds->isEmpty()) {
            return [];
        }

        /** @var EloquentBuilder<Model> $query */
        $query = $model::query()->whereKey($requestedAssetIds->all());

        $this->constrainAssetQueryToCurrentContext($query, new $model);

        $recordsByKey = $query->get()
            ->filter(fn (Model $record): bool => $this->canUseAssetRecord($record))
            ->keyBy(fn (Model $record): string => (string) $record->getKey());

        return $requestedAssetIds
            ->map(fn (int|string $assetId): mixed => $recordsByKey->get((string) $assetId)?->getKey())
            ->filter(fn (mixed $assetId): bool => $assetId !== null)
            ->values()
            ->all();
    }

    protected function getAllowedAssetTypes(Element $element): array
    {
        $assetTypes = isset($element->admin['asset_types']) && $element->admin['asset_types'] !== []
            ? $element->admin['asset_types']
            : ($element->type->admin['asset_types'] ?? []);

        if ($assetTypes === []) {
            return CapellCore::getAssets()
                ->keys()
                ->map(fn (string $assetType): string => $this->normalizeAssetType($assetType))
                ->values()
                ->all();
        }

        return collect($assetTypes)
            ->map(fn (mixed $assetType): string => $this->normalizeAssetType($assetType))
            ->filter(fn (string $assetType): bool => $this->hasRegisteredAssetType($assetType))
            ->values()
            ->all();
    }

    protected function normalizeAssetType(mixed $assetType): string
    {
        if ($assetType instanceof BackedEnum) {
            $assetType = $assetType->value;
        }

        return mb_strtolower((string) $assetType);
    }

    protected function hasRegisteredAssetType(string $assetType): bool
    {
        return CapellCore::hasAsset(Str::ucfirst($assetType));
    }

    protected function constrainAssetQueryToCurrentContext(EloquentBuilder $query, Model $assetModel): void
    {
        $table = $assetModel->getTable();
        $site = $this->getSite();
        $assetModelClass = $assetModel::class;

        if ($site !== null && Schema::hasColumn($table, 'site_id')) {
            $query->where(
                fn (EloquentBuilder $query): EloquentBuilder => $query->where('site_id', $site->getKey())
                    ->orWhereNull('site_id'),
            );
        }

        if (
            $this->page instanceof Model
            && $this->page instanceof $assetModelClass
        ) {
            $query->whereKeyNot($this->page->getKey());
        }

        $workspaceId = $this->getCurrentWorkspaceId();

        if ($workspaceId !== null && Schema::hasColumn($table, 'workspace_id')) {
            $query->where(
                fn (EloquentBuilder $query): EloquentBuilder => $query->where('workspace_id', $workspaceId)
                    ->orWhere('workspace_id', 0),
            );
        }
    }

    protected function canUseAssetRecord(Model $record): bool
    {
        if (Gate::getPolicyFor($record) === null) {
            return true;
        }

        try {
            return Gate::allows('view', $record);
        } catch (Throwable) {
            return false;
        }
    }

    protected function getCurrentWorkspaceId(): ?int
    {
        foreach ([$this->page, $this->layout] as $record) {
            if (! $record instanceof Model) {
                continue;
            }

            if (! array_key_exists('workspace_id', $record->getAttributes())) {
                continue;
            }

            return (int) $record->getAttribute('workspace_id');
        }

        return null;
    }

    protected function getCurrentElementAssetWorkspaceId(?Element $element = null): int
    {
        if ($element instanceof Element && array_key_exists('workspace_id', $element->getAttributes())) {
            return (int) $element->getAttribute('workspace_id');
        }

        return $this->getCurrentWorkspaceId() ?? 0;
    }

    /**
     * @return array<int>
     */
    protected function getReadableElementAssetWorkspaceIds(?Element $element = null): array
    {
        $workspaceId = $this->getCurrentElementAssetWorkspaceId($element);

        if ($workspaceId === 0) {
            return [0];
        }

        return [$workspaceId, 0];
    }

    /**
     * @return array<int>
     */
    protected function getCurrentContainerElementAssetWorkspaceIds(): array
    {
        $workspaceIds = Element::query()
            ->whereIn('key', $this->getContainerElementKeys())
            ->pluck('workspace_id')
            ->map(fn (mixed $workspaceId): int => (int) $workspaceId)
            ->push($this->getCurrentWorkspaceId() ?? 0)
            ->push(0)
            ->unique()
            ->values()
            ->all();

        return array_map(intval(...), $workspaceIds);
    }

    /**
     * @return array<int>
     */
    protected function getCurrentContainerElementIds(): array
    {
        return Element::query()
            ->whereIn('key', $this->getContainerElementKeys())
            ->pluck('id')
            ->map(fn (mixed $elementId): int => (int) $elementId)
            ->all();
    }

    protected function updateAssets(string $containerKey, int $elementIndex, ?string $oldContainerKey = null): void
    {
        $oldContainerKey ??= $containerKey;

        $assets = $this->assets[$containerKey][$elementIndex] ?? [];

        $element = $this->getContainerElement($containerKey, $elementIndex);

        $occurrence = $this->getContainerElementOccurrence($containerKey, $elementIndex);

        $elementHasPageAssets = $assets !== [] ? $this->elementHasPageAssets($element) : $this->inPageContext();

        $hasPageAssets = $assets !== [] ? $this->hasPageAssets($containerKey, $elementIndex) : $this->inPageContext();

        $assetIds = collect($assets)
            ->pluck('id')
            ->filter(fn (mixed $assetId): bool => is_int($assetId) || is_string($assetId))
            ->map(fn (int|string $assetId): int => (int) $assetId)
            ->filter(fn (int $assetId): bool => $assetId > 0)
            ->unique()
            ->values();

        $existingAssets = $element->assets()
            ->where('workspace_id', $this->getCurrentElementAssetWorkspaceId($element))
            ->where(
                fn (EloquentBuilder $query): EloquentBuilder => $query
                    ->where(
                        fn (EloquentBuilder $query): EloquentBuilder => $query
                            ->where('occurrence', $occurrence)
                            ->when(
                                $elementHasPageAssets ? fn (EloquentBuilder $query) => $query
                                    ->where([
                                        'container' => $oldContainerKey,
                                        'pageable_type' => $this->page->getMorphClass(),
                                        'pageable_id' => $this->page->getKey(),
                                    ]) : null,
                                fn (EloquentBuilder $query) => $query->whereNull(['container', 'pageable_id', 'pageable_type']),
                            ),
                    )
                    ->when(
                        $assetIds->isNotEmpty(),
                        fn (EloquentBuilder $query): EloquentBuilder => $query->orWhereIn(
                            $query->getModel()->getQualifiedKeyName(),
                            $assetIds->all(),
                        ),
                    ),
            )
            ->get();

        $existingAssetsByKey = $existingAssets
            ->mapWithKeys(fn (ElementAsset $elementAsset): array => [$elementAsset->asset_key => $elementAsset]);

        $existingAssetsById = $existingAssets
            ->keyBy(fn (ElementAsset $elementAsset): int => $elementAsset->getKey());

        if ($existingAssets->isNotEmpty()) {
            $activeElementAssetIds = $this->activeElementAssetIds($element);

            $currentAssets = collect($assets)
                ->filter(fn (array $elementAsset): bool => $existingAssetsByKey->has(sprintf('%s.%s', $elementAsset['asset_type'], $elementAsset['asset_id'])))
                ->mapWithKeys(fn (array $elementAsset): array => [sprintf('%s.%s', $elementAsset['asset_type'], $elementAsset['asset_id']) => $elementAsset]);

            $assetsToRemove = $currentAssets->isNotEmpty()
                ? $existingAssetsByKey->diffKeys($currentAssets)
                : $existingAssetsByKey;

            $assetsToRemove = $assetsToRemove->reject(
                fn (ElementAsset $elementAsset): bool => in_array((int) $elementAsset->getKey(), $activeElementAssetIds, true),
            );

            if ($assetsToRemove->isNotEmpty()) {
                $assetsToRemove->each(function (ElementAsset $elementAsset) use ($containerKey, $elementIndex, $element): void {
                    $searchIndex = $element->assets->search(fn (ElementAsset $asset): bool => $asset->id === $elementAsset->id);
                    if (is_int($searchIndex)) {
                        $element->assets->forget([$searchIndex]);
                    }

                    $this->removeAsset($containerKey, $elementIndex, $elementAsset->asset_id, $elementAsset->asset_type);

                    $elementAsset->delete();
                });
            }
        }

        if ($assets === []) {
            return;
        }

        collect($assets)->each(
            function (array $elementAsset) use ($existingAssetsById, $existingAssetsByKey, $element, $containerKey, $occurrence, $hasPageAssets): void {
                $key = sprintf('%s.%s', $elementAsset['asset_type'], $elementAsset['asset_id']);

                $order = $elementAsset['order'];

                $existingAsset = isset($elementAsset['id'])
                    ? $existingAssetsById->get((int) $elementAsset['id'])
                    : null;

                if (! $existingAsset instanceof ElementAsset) {
                    $existingAsset = $existingAssetsByKey->get($key);
                }

                if ($existingAsset instanceof ElementAsset) {
                    $existingAsset->order = $order;
                    $existingAsset->meta = $elementAsset['meta'] ?? [];
                    $existingAsset->occurrence = $occurrence;

                    if ($hasPageAssets) {
                        $existingAsset->container = $containerKey;
                        $existingAsset->pageable_id = $this->page->getKey();
                        $existingAsset->pageable_type = $this->page->getMorphClass();
                    } else {
                        $existingAsset->container = null;
                        $existingAsset->pageable_id = null;
                        $existingAsset->pageable_type = null;
                    }

                    $existingAsset->save();

                    return;
                }

                $this->createElementAsset(
                    element: $element,
                    containerKey: $containerKey,
                    occurrence: $occurrence,
                    hasPageAssets: $hasPageAssets,
                    order: $order,
                    asset: $elementAsset,
                );
            },
        );
    }

    /**
     * @return array<int>
     */
    protected function activeElementAssetIds(Element $element): array
    {
        $assetIds = [];

        foreach ($this->currentElementAssetData() as $elementAsset) {
            if (! isset($elementAsset['id'])) {
                continue;
            }

            $assetId = (int) $elementAsset['id'];

            if ($assetId > 0) {
                $assetIds[] = $assetId;
            }
        }

        return array_values(array_unique($assetIds));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function currentElementAssetData(): array
    {
        $elementAssets = [];

        foreach ($this->assets as $containerElements) {
            foreach ($containerElements as $containerElementAssets) {
                foreach ($containerElementAssets as $elementAsset) {
                    if (is_array($elementAsset)) {
                        $elementAssets[] = $elementAsset;
                    }
                }
            }
        }

        return $elementAssets;
    }

    protected function addElementAsset(
        Element $element,
        string $containerKey,
        string $type,
        bool $hasPageAssets,
        int|string $assetId,
        array $meta,
        int $occurrence,
        int $order,
    ): ElementAsset {
        $pageId = $hasPageAssets ? $this->page->getKey() : null;

        $elementAsset = $element->assets
            ->where([
                'asset_id' => $assetId,
                'asset_type' => $type,
                'occurrence' => $occurrence,
            ])
            ->when(
                $pageId,
                fn (SupportCollection $collection) => $collection->where([
                    'container' => $containerKey,
                    'pageable_id' => $pageId,
                    'pageable_type' => $this->page->getMorphClass(),
                ]),
            )
            ->first();

        if (! $elementAsset instanceof ElementAsset) {
            /** @var ElementAsset $elementAsset */
            $elementAsset = $element->assets()->newModelInstance([
                'meta' => $meta,
                'order' => $order,
                'layout_element_id' => $element->id,
                'workspace_id' => $this->getCurrentElementAssetWorkspaceId($element),
                'asset_type' => mb_strtolower($type),
                'asset_id' => $assetId,
                'occurrence' => $occurrence,
            ]);

            if ($pageId !== null) {
                $elementAsset->pageable_id = $pageId;
                $elementAsset->pageable_type = $this->page->getMorphClass();
                $elementAsset->container = $containerKey;
            }
        }

        return $elementAsset;
    }

    protected function createElementAsset(
        Element $element,
        string $containerKey,
        int $occurrence,
        bool $hasPageAssets,
        int $order,
        array $asset,
    ): ElementAsset {
        $attributes = [
            'layout_element_id' => $element->id,
            'workspace_id' => $this->getCurrentElementAssetWorkspaceId($element),
            'asset_type' => $asset['asset_type'],
            'asset_id' => $asset['asset_id'],
            'occurrence' => $occurrence,
        ];

        if ($hasPageAssets) {
            $attributes['pageable_id'] = $this->page->getKey();
            $attributes['pageable_type'] = $this->page->getMorphClass();
            $attributes['container'] = $containerKey;
        } else {
            $attributes['pageable_id'] = null;
            $attributes['pageable_type'] = null;
            $attributes['container'] = null;
        }

        /** @var ElementAsset|null $existing */
        $existing = ElementAsset::query()
            ->where($attributes)
            ->first();

        if ($existing instanceof ElementAsset) {
            $existing->order = $order;
            $existing->meta = $asset['meta'] ?? [];
            $existing->save();

            return $existing;
        }

        /** @var ElementAsset $elementAsset */
        $elementAsset = $element->assets()->make(array_merge([
            'meta' => $asset['meta'] ?? [],
            'order' => $order,
        ], $attributes));

        $elementAsset->save();

        return $elementAsset;
    }

    protected function removeAsset(string $containerKey, int $elementIndex, mixed $uuid, string $type): void
    {
        foreach ($this->assets[$containerKey][$elementIndex] as $index => $elementAsset) {
            if ($elementAsset['asset_id'] !== $uuid) {
                continue;
            }

            if ($elementAsset['asset_type'] !== $type) {
                continue;
            }

            unset($this->assets[$containerKey][$elementIndex][$index]);
        }
    }

    protected function removeSelectedAssets(string $containerKey, int $elementIndex): void
    {
        $this->assertCanUpdateLayout();

        foreach ($this->selectedRecords[$containerKey][$elementIndex] as $asset) {
            [$type, $uuid] = explode('.', (string) $asset);

            if (is_numeric($uuid)) {
                $uuid = (int) $uuid;
            }

            $this->removeAsset($containerKey, $elementIndex, $uuid, $type);
        }

        $this->assets[$containerKey][$elementIndex] = array_values($this->assets[$containerKey][$elementIndex]);

        $this->selectedRecords[$containerKey][$elementIndex] = [];

        $this->layoutUpdated();
    }

    protected function togglePageAssets(string $containerKey, int $elementIndex, ?Pageable $page): void
    {
        $this->assertCanUpdateLayout();

        $hasPageAssets = $page instanceof Pageable;

        $this->updatePageAssets($containerKey, $elementIndex, $hasPageAssets);

        $this->layoutUpdated();
    }

    protected function updateElementAsset(string $containerKey, int $elementIndex, int $index, array $data): void
    {
        $this->assertCanUpdateLayout();

        $elementAsset = $this->assets[$containerKey][$elementIndex][$index];

        $this->assets[$containerKey][$elementIndex][$index] = array_merge_recursive($elementAsset, $data);
    }

    protected function updateElementAssetContentState(string $containerKey, int $elementIndex, int $index, array $data): void
    {
        $this->assertCanEditContent();

        $elementAsset = $this->assets[$containerKey][$elementIndex][$index];

        $this->assets[$containerKey][$elementIndex][$index] = array_replace_recursive($elementAsset, $data);
    }

    protected function shouldAddPageAssets(string $containerKey, int $elementIndex): bool
    {
        if (! $this->inPageContext()) {
            return false;
        }

        $assets = $this->getElementAssets($containerKey, $elementIndex);

        if ($assets === []) {
            return true;
        }

        return collect($assets)->contains(
            fn (array $elementAsset): bool => $elementAsset['pageable_id'] === $this->page->getKey()
                && $elementAsset['pageable_type'] === $this->page->getMorphClass(),
        );
    }

    protected function getElementAssets(string $containerKey, int $elementIndex): array
    {
        return $this->assets[$containerKey][$elementIndex];
    }

    protected function countElementAssets(string $containerKey, int $elementIndex): int
    {
        return count($this->getElementAssets($containerKey, $elementIndex));
    }

    protected function getElementAsset(string $containerKey, int $elementIndex, int $index): ?array
    {
        return $this->assets[$containerKey][$elementIndex][$index] ?? null;
    }

    protected function getElementAssetsByType(string $containerKey, int $elementIndex, string $type): array
    {
        if (! isset($this->assets[$containerKey][$elementIndex])) {
            return [];
        }

        return array_column(
            array_filter($this->assets[$containerKey][$elementIndex], fn (array $elementAsset): bool => $elementAsset['asset_type'] === $type),
            'asset_id',
        );
    }

    protected function loadElementAssets(Element $element, string $containerKey, int $elementOccurrence): Collection
    {
        /** @var class-string<ElementAsset> $model */
        $model = ElementAsset::class;

        $assets = $model::query()
            ->with([
                'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
                'media',
            ])
            ->where('layout_element_id', $element->id)
            ->whereIn('workspace_id', $this->getReadableElementAssetWorkspaceIds($element))
            ->where('occurrence', $elementOccurrence)
            ->where(
                fn (EloquentBuilder $query): EloquentBuilder => $query->where('container', $containerKey)
                    ->orWhereNull('container'),
            )
            ->when(
                $this->page,
                fn (EloquentBuilder $query): EloquentBuilder => $query->where(
                    fn (EloquentBuilder $query): EloquentBuilder => $query->where([
                        'pageable_type' => $this->page->getMorphClass(),
                        'pageable_id' => $this->page->getKey(),
                    ])
                        ->orWhereNull(['pageable_type', 'pageable_id']),
                ),
                fn (EloquentBuilder $query): EloquentBuilder => $query->orWhereNull(['pageable_type', 'pageable_id']),
            )
            ->ordered()
            ->get()
            ->each->setRelation('element', $element);

        return $this->filterContainerElementAssets($assets, $containerKey, $elementOccurrence, $element);
    }

    protected function loadElementAssetsFor(Element $element, string $containerKey, int $elementIndex): Collection
    {
        $occurrence = $this->getContainerElementOccurrence($containerKey, $elementIndex);

        $elementAssets = collect($this->assets[$containerKey][$elementIndex] ?? []);

        if ($elementAssets->isEmpty()) {
            return new Collection;
        }

        $existingIds = $elementAssets
            ->filter(fn (array $asset): bool => isset($asset['id']))
            ->pluck('id')
            ->all();

        $newAssets = $elementAssets
            ->reject(fn (array $asset): bool => isset($asset['id']))
            ->all();

        $assets = $this->buildPreloadedElementAssets($existingIds, $newAssets);

        return $this->filterContainerElementAssets($assets, $containerKey, $occurrence, $element)
            ->each(fn (ElementAsset $asset): ElementAsset => $asset->setRelation('element', $element));
    }

    protected function preloadAllElementAssets(): ?Collection
    {
        $elementAssets = collect($this->currentElementAssetData());

        if ($elementAssets->isEmpty()) {
            return null;
        }

        $existingIds = $elementAssets
            ->filter(fn (array $asset): bool => isset($asset['id']))
            ->pluck('id')
            ->all();

        $newAssets = $elementAssets
            ->reject(fn (array $asset): bool => isset($asset['id']))
            ->all();

        return $this->buildPreloadedElementAssets($existingIds, $newAssets);
    }

    protected function buildPreloadedElementAssets(array $existingIds, array $newAssets): Collection
    {
        /** @var class-string<ElementAsset> $model */
        $model = ElementAsset::class;

        $existingAssets = $existingIds === []
            ? (new $model)->newCollection()
            : $model::query()
                ->whereKey($existingIds)
                ->whereIn('layout_element_id', $this->getCurrentContainerElementIds())
                ->whereIn('workspace_id', $this->getCurrentContainerElementAssetWorkspaceIds())
                ->when(
                    $this->page,
                    fn (EloquentBuilder $query) => $query->where(
                        fn (EloquentBuilder $query) => $query->where([
                            'pageable_type' => $this->page->getMorphClass(),
                            'pageable_id' => $this->page->getKey(),
                        ])
                            ->orWhereNull(['pageable_type', 'pageable_id']),
                    ),
                    fn (EloquentBuilder $query) => $query->whereNull(['pageable_type', 'pageable_id']),
                )
                ->when(
                    DB::getDriverName() === 'sqlite',
                    fn (BuilderContract $query): BuilderContract => $query->orderByRaw(
                        'CASE id '
                        . implode(' ', array_map(
                            fn (string $id, string $position): string => sprintf('WHEN %d THEN %d', $id, $position),
                            $existingIds,
                            array_keys($existingIds),
                        ))
                          . ' END',
                    ),
                    fn (BuilderContract $query): BuilderContract => $query->orderByRaw('FIELD(id, ' . implode(',', array_map(intval(...), $existingIds)) . ')'),
                )
                ->get();

        $newAssetsCollection = collect($newAssets)
            ->values()
            ->filter(fn (array $data): bool => in_array((int) ($data['workspace_id'] ?? $this->getCurrentElementAssetWorkspaceId()), $this->getCurrentContainerElementAssetWorkspaceIds(), true))
            ->map(fn (array $data) => $model::query()->newModelInstance()->forceFill($data));

        $allAssets = (new $model)->newCollection(array_merge($existingAssets->all(), $newAssetsCollection->all()));

        $eloquentCollection = new Collection($allAssets->all());

        return $eloquentCollection->load(['asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations())])
            ->filter(fn (ElementAsset $elementAsset): bool => $this->canUseAssetRecord($elementAsset->asset))
            ->map(fn (ElementAsset $elementAsset): ElementAsset => $elementAsset);
    }

    protected function filterContainerElementAssets(Collection $assets, string $containerKey, int $elementOccurrence, ?Element $element = null): SupportCollection|Enumerable
    {
        $currentWorkspaceId = $this->getCurrentElementAssetWorkspaceId($element);
        $readableWorkspaceIds = $this->getReadableElementAssetWorkspaceIds($element);

        $filteredAssets = $assets->filter(function (ElementAsset $elementAsset) use ($containerKey, $elementOccurrence, $readableWorkspaceIds): bool {
            if (! in_array($elementAsset->workspace_id, $readableWorkspaceIds, true)) {
                return false;
            }

            if ((int) $elementAsset->occurrence !== $elementOccurrence) {
                return false;
            }

            if ($elementAsset->container === null) {
                return true;
            }

            if ($elementAsset->container !== $containerKey) {
                return false;
            }

            if ($elementAsset->pageable_type === null && $elementAsset->pageable_id === null) {
                return true;
            }

            if (! $this->inPageContext()) {
                return false;
            }

            return $elementAsset->pageable_type === $this->page->getMorphClass()
                && $elementAsset->pageable_id === $this->page->getKey();
        })->values();

        return $filteredAssets
            ->groupBy(fn (ElementAsset $elementAsset): string => implode(':', [
                $elementAsset->asset_type,
                $elementAsset->asset_id,
                $elementAsset->occurrence,
            ]))
            ->map(fn (SupportCollection $matchingAssets): ElementAsset => $matchingAssets
                ->first(fn (ElementAsset $elementAsset): bool => $elementAsset->workspace_id === $currentWorkspaceId)
                ?? $matchingAssets->first())
            ->sortBy(fn (ElementAsset $elementAsset): int => $elementAsset->order)
            ->values();
    }

    protected function elementAssetMatchesState(ElementAsset $asset, array $elementAssetData, string $containerKey, string $oldContainerKey, int $occurrence, Element $element): bool
    {
        if ((int) $asset->layout_element_id !== (int) $element->getKey()) {
            return false;
        }

        if (isset($elementAssetData['layout_element_id']) && (int) $elementAssetData['layout_element_id'] !== (int) $asset->layout_element_id) {
            return false;
        }

        if (isset($elementAssetData['asset_type']) && $elementAssetData['asset_type'] !== $asset->asset_type) {
            return false;
        }

        if (isset($elementAssetData['asset_id']) && $elementAssetData['asset_id'] !== $asset->asset_id) {
            return false;
        }

        if (! in_array($asset->workspace_id, $this->getReadableElementAssetWorkspaceIds($element), true)) {
            return false;
        }

        if ($asset->container !== null && ! in_array($asset->container, [$containerKey, $oldContainerKey], true)) {
            return false;
        }

        if (! $this->inPageContext()) {
            return $asset->pageable_type === null && $asset->pageable_id === null;
        }

        return ($asset->pageable_type === null && $asset->pageable_id === null)
            || ($asset->pageable_type === $this->page->getMorphClass()
                && $asset->pageable_id === $this->page->getKey());
    }

    protected function getAssetRelations(): array
    {
        $relations = [];
        foreach (CapellCore::getAssets() as $asset) {
            $model = $asset->model;
            $relations[$model] = method_exists($model, 'getMorphRelations') ? $model::getMorphRelations() : [];

            if (! in_array('site', $relations[$model], true) && method_exists($model, 'site')) {
                $relations[$model][] = 'site';
            }

            if (! in_array('related', $relations[$model], true) && method_exists($model, 'related')) {
                $relations[$model][] = 'related';
            }
        }

        $relations[Page::class] ??= Page::getMorphRelations();
        if (! in_array('related', $relations[Page::class], true)) {
            $relations[Page::class][] = 'related';
        }

        return $relations;
    }

    protected function reloadContainerElementAsset(string $containerKey, int $elementIndex, int $index): void
    {
        $element = $this->getContainerElement($containerKey, $elementIndex);

        $assets = $element->assets;
        $assets[$index] = $assets[$index]->fresh();
        $element->setRelation('assets', $assets);
    }

    protected function deleteRemovedElementAssets(): void
    {
        foreach ($this->originalAssets as $containerKey => $originalElementAssets) {
            foreach ($originalElementAssets as $elementIndex => $originalAssets) {
                $currentAssets = $this->assets[$containerKey][$elementIndex] ?? [];

                $originalKeys = collect($originalAssets)
                    ->map(static fn (array $asset): string => $asset['asset_type'] . ':' . $asset['asset_id'] . ':' . $asset['occurrence'] . ':' . $asset['original_container_key'])
                    ->values()
                    ->all();

                $currentKeys = collect($currentAssets)
                    ->map(static fn (array $asset): string => $asset['asset_type'] . ':' . $asset['asset_id'] . ':' . $asset['occurrence'] . ':' . $containerKey)
                    ->values()
                    ->all();

                $removedKeys = array_diff($originalKeys, $currentKeys);

                if ($removedKeys === []) {
                    continue;
                }

                $hasPageAssets = false;
                if ($this->inPageContext()) {
                    $hasPageAssets = collect($originalAssets)->contains(
                        fn (array $asset): bool => $asset['pageable_id'] === $this->page->getKey()
                            && $asset['pageable_type'] === $this->page->getMorphClass(),
                    );
                }

                foreach ($originalAssets as $asset) {
                    $key = $asset['asset_type'] . ':' . $asset['asset_id'] . ':' . $asset['occurrence'] . ':' . $asset['original_container_key'];
                    if (! in_array($key, $removedKeys, true)) {
                        continue;
                    }

                    ElementAsset::query()
                        ->when(
                            isset($asset['id']),
                            fn (EloquentBuilder $query): EloquentBuilder => $query->whereKey((int) $asset['id']),
                            fn (EloquentBuilder $query): EloquentBuilder => $query->where([
                                'asset_id' => $asset['asset_id'],
                                'asset_type' => $asset['asset_type'],
                                'occurrence' => $asset['occurrence'],
                                'layout_element_id' => $asset['original_element_id'],
                                'workspace_id' => (int) ($asset['workspace_id'] ?? $this->getCurrentElementAssetWorkspaceId()),
                            ]),
                        )
                        ->when(
                            $hasPageAssets,
                            fn (EloquentBuilder $query) => $query->where([
                                'container' => $asset['original_container_key'],
                                'pageable_type' => $this->page->getMorphClass(),
                                'pageable_id' => $this->page->getKey(),
                            ]),
                        )
                        ->delete();
                }
            }
        }
    }
}
