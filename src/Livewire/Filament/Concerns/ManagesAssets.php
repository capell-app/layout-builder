<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use BackedEnum;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Models\BlockAsset;
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
    public function reorderAssets(string $containerKey, int $blockIndex, int $index, int $newIndex): void
    {
        $this->assertCanUpdateLayout();

        $assets = $this->assets[$containerKey][$blockIndex];

        $blockAsset = $this->getBlockAsset($containerKey, $blockIndex, $index);

        throw_if($blockAsset === null || $blockAsset === [], Exception::class, sprintf('Asset %d not found for container: %s block: %d', $index, $containerKey, $blockIndex));

        unset($assets[$index]);

        $assets = array_values($assets);

        array_splice($assets, $newIndex, 0, [$blockAsset]);

        $order = 1;
        $assets = array_map(
            function (array $asset) use (&$order): array {
                $asset['order'] = $order;
                $order++;

                return $asset;
            },
            $assets,
        );

        $this->assets[$containerKey][$blockIndex] = $assets;

        $this->layoutUpdated();
    }

    public function moveAssetUp(string $containerKey, int $blockIndex, int $assetIndex): void
    {
        if (! $this->canMoveAssetUp($containerKey, $blockIndex, $assetIndex)) {
            return;
        }

        $this->reorderAssets($containerKey, $blockIndex, $assetIndex, $assetIndex - 1);
    }

    public function moveAssetDown(string $containerKey, int $blockIndex, int $assetIndex): void
    {
        if (! $this->canMoveAssetDown($containerKey, $blockIndex, $assetIndex)) {
            return;
        }

        $this->reorderAssets($containerKey, $blockIndex, $assetIndex, $assetIndex + 1);
    }

    public function canMoveAssetUp(string $containerKey, int $blockIndex, int $assetIndex): bool
    {
        return $assetIndex > 0 && isset($this->assets[$containerKey][$blockIndex][$assetIndex]);
    }

    public function canMoveAssetDown(string $containerKey, int $blockIndex, int $assetIndex): bool
    {
        return isset($this->assets[$containerKey][$blockIndex][$assetIndex + 1]);
    }

    public function hasPageAssets(string $containerKey, int $blockIndex): bool
    {
        if (! $this->inPageContext()) {
            return false;
        }

        $assets = $this->getBlockAssets($containerKey, $blockIndex);

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

    public function blockHasPageAssets(Block $block): bool
    {
        if (! $this->inPageContext()) {
            return $block->assets()->whereNotNull('pageable_type')->whereNotNull('pageable_id')->exists();
        }

        if (property_exists($block, 'page_assets_count')) {
            return $block->page_assets_count > 0;
        }

        return $block
            ->assets()
            ->where([
                'pageable_type' => $this->page->getMorphClass(),
                'pageable_id' => $this->page->getKey(),
            ])
            ->exists();
    }

    public function blockHasGlobalAssets(Block $block): bool
    {
        if (property_exists($block, 'global_assets_count')) {
            return $block->global_assets_count > 0;
        }

        return $block->assets()->whereNull(['pageable_type', 'pageable_id'])->exists();
    }

    public function selectAllAssets(string $containerKey, int $blockIndex): void
    {
        $this->assertCanUpdateLayout();

        $this->selectedRecords[$containerKey][$blockIndex] = $this->getAllSelectableAssetsKeys(
            $containerKey,
            $blockIndex,
        );
    }

    public function deSelectAllAssets(string $containerKey, int $blockIndex): void
    {
        $this->assertCanUpdateLayout();

        $this->selectedRecords[$containerKey][$blockIndex] = [];
    }

    public function getBlockAssetTypes(Block $block): array
    {
        return $this->getAllowedAssetTypes($block);
    }

    public function getCurrentBlockAssetWorkspaceId(?Block $block = null): int
    {
        if ($block instanceof Block && array_key_exists('workspace_id', $block->getAttributes())) {
            return (int) $block->getAttribute('workspace_id');
        }

        return $this->getCurrentWorkspaceId() ?? 0;
    }

    public function togglePageAssets(string $containerKey, int $blockIndex, ?Pageable $page): void
    {
        $this->assertCanUpdateLayout();

        $hasPageAssets = $page instanceof Pageable;

        $this->updatePageAssets($containerKey, $blockIndex, $hasPageAssets);

        $this->layoutUpdated();
    }

    public function shouldAddPageAssets(string $containerKey, int $blockIndex): bool
    {
        if (! $this->inPageContext()) {
            return false;
        }

        $assets = $this->getBlockAssets($containerKey, $blockIndex);

        if ($assets === []) {
            return true;
        }

        return collect($assets)->contains(
            fn (array $blockAsset): bool => $blockAsset['pageable_id'] === $this->page->getKey()
                && $blockAsset['pageable_type'] === $this->page->getMorphClass(),
        );
    }

    public function getBlockAssets(string $containerKey, int $blockIndex): array
    {
        return $this->assets[$containerKey][$blockIndex];
    }

    public function countBlockAssets(string $containerKey, int $blockIndex): int
    {
        return count($this->getBlockAssets($containerKey, $blockIndex));
    }

    public function getBlockAsset(string $containerKey, int $blockIndex, int $index): ?array
    {
        return $this->assets[$containerKey][$blockIndex][$index] ?? null;
    }

    public function getBlockAssetsByType(string $containerKey, int $blockIndex, string $type): array
    {
        if (! isset($this->assets[$containerKey][$blockIndex])) {
            return [];
        }

        return array_column(
            array_filter($this->assets[$containerKey][$blockIndex], fn (array $blockAsset): bool => $blockAsset['asset_type'] === $type),
            'asset_id',
        );
    }

    public function getSelectedAssets(string $containerKey, int $blockIndex): array
    {
        return $this->selectedRecords[$containerKey][$blockIndex] ?? [];
    }

    public function removeSelectedAssets(string $containerKey, int $blockIndex): void
    {
        $this->assertCanUpdateLayout();

        foreach ($this->selectedRecords[$containerKey][$blockIndex] as $asset) {
            [$type, $uuid] = explode('.', (string) $asset);

            if (is_numeric($uuid)) {
                $uuid = (int) $uuid;
            }

            $this->removeAsset($containerKey, $blockIndex, $uuid, $type);
        }

        $this->assets[$containerKey][$blockIndex] = array_values($this->assets[$containerKey][$blockIndex]);

        $this->selectedRecords[$containerKey][$blockIndex] = [];

        $this->layoutUpdated();
    }

    public function updateBlockAssetContentState(string $containerKey, int $blockIndex, int $index, array $data): void
    {
        $this->assertCanEditContent();

        $blockAsset = $this->assets[$containerKey][$blockIndex][$index];

        $this->assets[$containerKey][$blockIndex][$index] = array_replace_recursive($blockAsset, $data);
    }

    public function getAssetRelations(): array
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

    public function reloadContainerBlockAsset(string $containerKey, int $blockIndex, int $index): void
    {
        $block = $this->getContainerBlock($containerKey, $blockIndex);

        $assets = $block->assets;
        $assets[$index] = $assets[$index]->fresh();
        $block->setRelation('assets', $assets);
    }

    protected function moveContainerBlockAssets(string $originalContainer, int $originalIndex, string $containerKey, int $blockIndex): void
    {
        $block = $this->assets[$originalContainer][$originalIndex];
        $blockSelectedRecords = $this->selectedRecords[$originalContainer][$originalIndex] ?? [];

        $assets = $this->assets[$containerKey] ?? [];
        $assets = array_merge(array_slice($assets, 0, $blockIndex), [$block], array_slice($assets, $blockIndex));
        $this->assets[$containerKey] = $assets;

        if ($containerKey !== $originalContainer) {
            unset($this->assets[$originalContainer][$originalIndex]);
            $this->assets[$originalContainer] = array_values($this->assets[$originalContainer]);
        }

        $selectedRecords = $this->selectedRecords[$containerKey] ?? [];
        $selectedRecords = array_merge(array_slice($selectedRecords, 0, $blockIndex), [$blockSelectedRecords], array_slice($selectedRecords, $blockIndex));
        $this->selectedRecords[$containerKey] = $selectedRecords;

        if ($containerKey !== $originalContainer && isset($this->selectedRecords[$originalContainer][$originalIndex])) {
            unset($this->selectedRecords[$originalContainer][$originalIndex]);
            $this->selectedRecords[$originalContainer] = array_values($this->selectedRecords[$originalContainer]);
        }
    }

    protected function updatePageAssets(string $containerKey, int $blockIndex, ?bool $hasPageAssets = null): void
    {
        if (! $this->assets[$containerKey][$blockIndex]) {
            return;
        }

        if ($hasPageAssets === null) {
            $hasPageAssets = $this->hasPageAssets($containerKey, $blockIndex);
        }

        foreach ($this->assets[$containerKey][$blockIndex] as $assetIndex => $asset) {
            if ($hasPageAssets) {
                $this->assets[$containerKey][$blockIndex][$assetIndex]['pageable_id'] = $this->page->getKey();
                $this->assets[$containerKey][$blockIndex][$assetIndex]['pageable_type'] = $this->page->getMorphClass();
            } else {
                $this->assets[$containerKey][$blockIndex][$assetIndex]['pageable_id'] = null;
                $this->assets[$containerKey][$blockIndex][$assetIndex]['pageable_type'] = null;
            }
        }
    }

    protected function mapBlockAssets(Block $block, string $containerKey, ?string $oldContainerKey = null): array
    {
        return $block->assets->map(
            static function (BlockAsset $blockAsset) use ($containerKey, $oldContainerKey): array {
                $asset = [
                    'id' => $blockAsset->id,
                    'block_id' => $blockAsset->block_id,
                    'workspace_id' => $blockAsset->workspace_id,
                    'asset_id' => $blockAsset->asset_id,
                    'asset_type' => $blockAsset->asset_type,
                    'meta' => $blockAsset->meta,
                    'order' => $blockAsset->order,
                    'occurrence' => $blockAsset->occurrence,
                ];

                if ($blockAsset->pageable_id !== null && $blockAsset->pageable_type !== null) {
                    $asset['pageable_id'] = $blockAsset->pageable_id;
                    $asset['pageable_type'] = $blockAsset->pageable_type;
                    $asset['container'] = $containerKey;
                }

                if ($oldContainerKey !== null && $oldContainerKey !== '') {
                    $asset['old_container'] = $oldContainerKey;
                }

                return $asset;
            },
        )->all();
    }

    protected function setupBlockAssets(string $containerKey, int $blockIndex, array $blockAssets, ?Collection $allBlockAssets, Block $block): Collection
    {
        $assets = new Collection;

        if (! $allBlockAssets instanceof Collection || $allBlockAssets->isEmpty()) {
            return $assets;
        }

        /** @var Collection<int, BlockAsset> $allBlockAssets */
        $occurrence = $this->getContainerBlockOccurrence($containerKey, $blockIndex);

        foreach ($blockAssets as $blockAssetData) {
            $type = $blockAssetData['asset_type'];
            $assetId = is_numeric($blockAssetData['asset_id']) ? (int) $blockAssetData['asset_id'] : $blockAssetData['asset_id'];

            $oldContainerKey = $blockAssetData['old_container'] ?? $containerKey;

            /** @var ?BlockAsset $matchingAsset */
            $matchingAsset = isset($blockAssetData['id'])
                ? $allBlockAssets->first(fn (BlockAsset $asset): bool => $asset->getKey() === (int) $blockAssetData['id']
                    && $this->blockAssetMatchesState($asset, $blockAssetData, $containerKey, $oldContainerKey, $occurrence, $block))
                : null;

            $matchingAsset ??= $allBlockAssets->first(function (BlockAsset $asset) use ($type, $assetId, $oldContainerKey, $occurrence, $block): bool {
                if ((int) $asset->block_id !== (int) $block->getKey()) {
                    return false;
                }

                if (! in_array($asset->workspace_id, $this->getReadableBlockAssetWorkspaceIds($block), true)) {
                    return false;
                }

                $matchesBlock = $asset->asset_type === $type
                    && $asset->asset_id === $assetId;

                if (! $matchesBlock) {
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

            $blockAsset = clone $matchingAsset;
            $blockAsset->order = $blockAssetData['order'] ?? $blockAsset->order;
            $blockAsset->occurrence = $blockAssetData['occurrence'] ?? $occurrence;
            $blockAsset->pageable_id = $blockAssetData['pageable_id'] ?? null;
            $blockAsset->pageable_type = $blockAssetData['pageable_type'] ?? null;

            $assets->push($blockAsset);
        }

        return $assets;
    }

    protected function setupSelectedAssets(): void
    {
        $this->selectedRecords = [];

        foreach ($this->containers as $containerKey => $container) {
            $this->selectedRecords[$containerKey] = [];

            foreach ($container['blocks'] as $blockIndex => $block) {
                $this->selectedRecords[$containerKey][$blockIndex] = [];
            }
        }
    }

    protected function saveOriginalAssets(): void
    {
        $originalAssets = [];

        foreach ($this->assets as $containerKey => $containerBlocks) {
            foreach ($containerBlocks as $blockIndex => $blockAssets) {
                $containerBlock = $this->getContainerBlock($containerKey, $blockIndex);

                foreach ($blockAssets as $blockAssetIndex => $blockAsset) {
                    $blockAsset['original_container_key'] = $containerKey;
                    $blockAsset['original_block_id'] = $containerBlock->id;
                    $blockAsset['original_block_key'] = $containerBlock->key;

                    $originalAssets[$containerKey][$blockIndex][$blockAssetIndex] = $blockAsset;
                }
            }
        }

        $this->originalAssets = $originalAssets;
    }

    protected function getAllSelectableAssetsKeys(string $containerKey, int $blockIndex): array
    {
        return collect($this->assets[$containerKey][$blockIndex])
            ->map(fn (array $blockAsset): string => sprintf('%s.%s', $blockAsset['asset_type'], $blockAsset['asset_id']))
            ->values()
            ->all();
    }

    protected function addAssets(string $containerKey, int $blockIndex, ?bool $hasPageAssets, string $type, mixed $assets, array $assetsMeta = []): void
    {
        $this->assertCanUpdateLayout();

        if (! isset($this->assets[$containerKey][$blockIndex])) {
            $this->assets[$containerKey][$blockIndex] = [];
        }

        $block = $this->getContainerBlock($containerKey, $blockIndex);

        $validatedAssetIds = $this->getValidatedAssetIds($block, $type, $assets);

        if ($validatedAssetIds === []) {
            return;
        }

        $occurrence = $this->getContainerBlockOccurrence($containerKey, $blockIndex);

        $order = $this->countBlockAssets($containerKey, $blockIndex);

        foreach ($validatedAssetIds as $assetId) {
            $order++;

            $meta = $assetsMeta[$assetId] ?? [];

            $asset = [
                'asset_id' => $assetId,
                'asset_type' => $type,
                'meta' => $meta,
                'block_id' => $block->id,
                'order' => $order,
                'occurrence' => $occurrence,
            ];

            if ($hasPageAssets === true) {
                $asset['pageable_id'] = $this->page->getKey();
                $asset['pageable_type'] = $this->page->getMorphClass();
                $asset['container'] = $containerKey;
            }

            $this->assets[$containerKey][$blockIndex][] = $asset;

            $blockAsset = $this->addBlockAsset(
                block: $block,
                containerKey: $containerKey,
                type: $type,
                hasPageAssets: $hasPageAssets,
                assetId: $assetId,
                meta: $meta,
                occurrence: $occurrence,
                order: $order,
            );

            $blockAsset->setRelation('block', $block);

            $block->assets->add($blockAsset);
        }

        $block->assets->load([
            'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
        ]);

        $this->containerBlocks[$containerKey][$blockIndex] = $block;
    }

    protected function getValidatedAssetIds(Block $block, string $type, mixed $assetIds): array
    {
        $normalizedType = $this->normalizeAssetType($type);

        if (! in_array($normalizedType, $this->getAllowedAssetTypes($block), true)) {
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

    protected function getAllowedAssetTypes(Block $block): array
    {
        $assetTypes = isset($block->admin['asset_types']) && $block->admin['asset_types'] !== []
            ? $block->admin['asset_types']
            : ($block->type->admin['asset_types'] ?? []);

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

    /**
     * @return array<int>
     */
    protected function getReadableBlockAssetWorkspaceIds(?Block $block = null): array
    {
        $workspaceId = $this->getCurrentBlockAssetWorkspaceId($block);

        if ($workspaceId === 0) {
            return [0];
        }

        return [$workspaceId, 0];
    }

    /**
     * @return array<int>
     */
    protected function getCurrentContainerBlockAssetWorkspaceIds(): array
    {
        $workspaceIds = Block::query()
            ->whereIn('key', $this->getContainerBlockKeys())
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
    protected function getCurrentContainerBlockIds(): array
    {
        return Block::query()
            ->whereIn('key', $this->getContainerBlockKeys())
            ->pluck('id')
            ->map(fn (mixed $blockId): int => (int) $blockId)
            ->all();
    }

    protected function updateAssets(string $containerKey, int $blockIndex, ?string $oldContainerKey = null): void
    {
        $oldContainerKey ??= $containerKey;

        $assets = $this->assets[$containerKey][$blockIndex] ?? [];

        $block = $this->getContainerBlock($containerKey, $blockIndex);

        $occurrence = $this->getContainerBlockOccurrence($containerKey, $blockIndex);

        $blockHasPageAssets = $assets !== [] ? $this->blockHasPageAssets($block) : $this->inPageContext();

        $hasPageAssets = $assets !== [] ? $this->hasPageAssets($containerKey, $blockIndex) : $this->inPageContext();

        $assetIds = collect($assets)
            ->pluck('id')
            ->filter(fn (mixed $assetId): bool => is_int($assetId) || is_string($assetId))
            ->map(fn (int|string $assetId): int => (int) $assetId)
            ->filter(fn (int $assetId): bool => $assetId > 0)
            ->unique()
            ->values();

        $existingAssets = $block->assets()
            ->where('workspace_id', $this->getCurrentBlockAssetWorkspaceId($block))
            ->where(
                fn (EloquentBuilder $query): EloquentBuilder => $query
                    ->where(
                        fn (EloquentBuilder $query): EloquentBuilder => $query
                            ->where('occurrence', $occurrence)
                            ->when(
                                $blockHasPageAssets ? fn (EloquentBuilder $query) => $query
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
            ->mapWithKeys(fn (BlockAsset $blockAsset): array => [$blockAsset->asset_key => $blockAsset]);

        $existingAssetsById = $existingAssets
            ->keyBy(fn (BlockAsset $blockAsset): int => $blockAsset->getKey());

        if ($existingAssets->isNotEmpty()) {
            $activeBlockAssetIds = $this->activeBlockAssetIds($block);

            $currentAssets = collect($assets)
                ->filter(fn (array $blockAsset): bool => $existingAssetsByKey->has(sprintf('%s.%s', $blockAsset['asset_type'], $blockAsset['asset_id'])))
                ->mapWithKeys(fn (array $blockAsset): array => [sprintf('%s.%s', $blockAsset['asset_type'], $blockAsset['asset_id']) => $blockAsset]);

            $assetsToRemove = $currentAssets->isNotEmpty()
                ? $existingAssetsByKey->diffKeys($currentAssets)
                : $existingAssetsByKey;

            $assetsToRemove = $assetsToRemove->reject(
                fn (BlockAsset $blockAsset): bool => in_array((int) $blockAsset->getKey(), $activeBlockAssetIds, true),
            );

            if ($assetsToRemove->isNotEmpty()) {
                $assetsToRemove->each(function (BlockAsset $blockAsset) use ($containerKey, $blockIndex, $block): void {
                    $searchIndex = $block->assets->search(fn (BlockAsset $asset): bool => $asset->id === $blockAsset->id);
                    if (is_int($searchIndex)) {
                        $block->assets->forget([$searchIndex]);
                    }

                    $this->removeAsset($containerKey, $blockIndex, $blockAsset->asset_id, $blockAsset->asset_type);

                    $blockAsset->delete();
                });
            }
        }

        if ($assets === []) {
            return;
        }

        collect($assets)->each(
            function (array $blockAsset) use ($existingAssetsById, $existingAssetsByKey, $block, $containerKey, $occurrence, $hasPageAssets): void {
                $key = sprintf('%s.%s', $blockAsset['asset_type'], $blockAsset['asset_id']);

                $order = $blockAsset['order'];

                $existingAsset = isset($blockAsset['id'])
                    ? $existingAssetsById->get((int) $blockAsset['id'])
                    : null;

                if (! $existingAsset instanceof BlockAsset) {
                    $existingAsset = $existingAssetsByKey->get($key);
                }

                if ($existingAsset instanceof BlockAsset) {
                    $existingAsset->order = $order;
                    $existingAsset->meta = $blockAsset['meta'] ?? [];
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

                $this->createBlockAsset(
                    block: $block,
                    containerKey: $containerKey,
                    occurrence: $occurrence,
                    hasPageAssets: $hasPageAssets,
                    order: $order,
                    asset: $blockAsset,
                );
            },
        );
    }

    /**
     * @return array<int>
     */
    protected function activeBlockAssetIds(Block $block): array
    {
        $assetIds = [];

        foreach ($this->currentBlockAssetData() as $blockAsset) {
            if (! isset($blockAsset['id'])) {
                continue;
            }

            $assetId = (int) $blockAsset['id'];

            if ($assetId > 0) {
                $assetIds[] = $assetId;
            }
        }

        return array_values(array_unique($assetIds));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function currentBlockAssetData(): array
    {
        $blockAssets = [];

        foreach ($this->assets as $containerBlocks) {
            foreach ($containerBlocks as $containerBlockAssets) {
                foreach ($containerBlockAssets as $blockAsset) {
                    if (is_array($blockAsset)) {
                        $blockAssets[] = $blockAsset;
                    }
                }
            }
        }

        return $blockAssets;
    }

    protected function addBlockAsset(
        Block $block,
        string $containerKey,
        string $type,
        bool $hasPageAssets,
        int|string $assetId,
        array $meta,
        int $occurrence,
        int $order,
    ): BlockAsset {
        $pageId = $hasPageAssets ? $this->page->getKey() : null;

        $blockAsset = $block->assets
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

        if (! $blockAsset instanceof BlockAsset) {
            /** @var BlockAsset $blockAsset */
            $blockAsset = $block->assets()->newModelInstance([
                'meta' => $meta,
                'order' => $order,
                'block_id' => $block->id,
                'workspace_id' => $this->getCurrentBlockAssetWorkspaceId($block),
                'asset_type' => mb_strtolower($type),
                'asset_id' => $assetId,
                'occurrence' => $occurrence,
            ]);

            if ($pageId !== null) {
                $blockAsset->pageable_id = $pageId;
                $blockAsset->pageable_type = $this->page->getMorphClass();
                $blockAsset->container = $containerKey;
            }
        }

        return $blockAsset;
    }

    protected function createBlockAsset(
        Block $block,
        string $containerKey,
        int $occurrence,
        bool $hasPageAssets,
        int $order,
        array $asset,
    ): BlockAsset {
        $attributes = [
            'block_id' => $block->id,
            'workspace_id' => $this->getCurrentBlockAssetWorkspaceId($block),
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

        /** @var BlockAsset|null $existing */
        $existing = BlockAsset::query()
            ->where($attributes)
            ->first();

        if ($existing instanceof BlockAsset) {
            $existing->order = $order;
            $existing->meta = $asset['meta'] ?? [];
            $existing->save();

            return $existing;
        }

        /** @var BlockAsset $blockAsset */
        $blockAsset = $block->assets()->make(array_merge([
            'meta' => $asset['meta'] ?? [],
            'order' => $order,
        ], $attributes));

        $blockAsset->save();

        return $blockAsset;
    }

    protected function removeAsset(string $containerKey, int $blockIndex, mixed $uuid, string $type): void
    {
        foreach ($this->assets[$containerKey][$blockIndex] as $index => $blockAsset) {
            if ($blockAsset['asset_id'] !== $uuid) {
                continue;
            }

            if ($blockAsset['asset_type'] !== $type) {
                continue;
            }

            unset($this->assets[$containerKey][$blockIndex][$index]);
        }
    }

    protected function updateBlockAsset(string $containerKey, int $blockIndex, int $index, array $data): void
    {
        $this->assertCanUpdateLayout();

        $blockAsset = $this->assets[$containerKey][$blockIndex][$index];

        $this->assets[$containerKey][$blockIndex][$index] = array_merge_recursive($blockAsset, $data);
    }

    protected function loadBlockAssets(Block $block, string $containerKey, int $blockOccurrence): Collection
    {
        /** @var class-string<BlockAsset> $model */
        $model = BlockAsset::class;

        $assets = $model::query()
            ->with([
                'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
                'media',
            ])
            ->where('block_id', $block->id)
            ->whereIn('workspace_id', $this->getReadableBlockAssetWorkspaceIds($block))
            ->where('occurrence', $blockOccurrence)
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
            ->each->setRelation('block', $block);

        return $this->filterContainerBlockAssets($assets, $containerKey, $blockOccurrence, $block);
    }

    protected function loadBlockAssetsFor(Block $block, string $containerKey, int $blockIndex): Collection
    {
        $occurrence = $this->getContainerBlockOccurrence($containerKey, $blockIndex);

        $blockAssets = collect($this->assets[$containerKey][$blockIndex] ?? []);

        if ($blockAssets->isEmpty()) {
            return new Collection;
        }

        $existingIds = $blockAssets
            ->filter(fn (array $asset): bool => isset($asset['id']))
            ->pluck('id')
            ->all();

        $newAssets = $blockAssets
            ->reject(fn (array $asset): bool => isset($asset['id']))
            ->all();

        $assets = $this->buildPreloadedBlockAssets($existingIds, $newAssets);

        return $this->filterContainerBlockAssets($assets, $containerKey, $occurrence, $block)
            ->each(fn (BlockAsset $asset): BlockAsset => $asset->setRelation('block', $block));
    }

    protected function preloadAllBlockAssets(): ?Collection
    {
        $blockAssets = collect($this->currentBlockAssetData());

        if ($blockAssets->isEmpty()) {
            return null;
        }

        $existingIds = $blockAssets
            ->filter(fn (array $asset): bool => isset($asset['id']))
            ->pluck('id')
            ->all();

        $newAssets = $blockAssets
            ->reject(fn (array $asset): bool => isset($asset['id']))
            ->all();

        return $this->buildPreloadedBlockAssets($existingIds, $newAssets);
    }

    protected function buildPreloadedBlockAssets(array $existingIds, array $newAssets): Collection
    {
        /** @var class-string<BlockAsset> $model */
        $model = BlockAsset::class;

        $existingAssets = $existingIds === []
            ? (new $model)->newCollection()
            : $model::query()
                ->whereKey($existingIds)
                ->whereIn('block_id', $this->getCurrentContainerBlockIds())
                ->whereIn('workspace_id', $this->getCurrentContainerBlockAssetWorkspaceIds())
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
            ->filter(fn (array $data): bool => in_array((int) ($data['workspace_id'] ?? $this->getCurrentBlockAssetWorkspaceId()), $this->getCurrentContainerBlockAssetWorkspaceIds(), true))
            ->map(fn (array $data) => $model::query()->newModelInstance()->forceFill($data));

        $allAssets = (new $model)->newCollection(array_merge($existingAssets->all(), $newAssetsCollection->all()));

        $eloquentCollection = new Collection($allAssets->all());

        return $eloquentCollection->load(['asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations())])
            ->filter(fn (BlockAsset $blockAsset): bool => $this->canUseAssetRecord($blockAsset->asset))
            ->map(fn (BlockAsset $blockAsset): BlockAsset => $blockAsset);
    }

    protected function filterContainerBlockAssets(Collection $assets, string $containerKey, int $blockOccurrence, ?Block $block = null): SupportCollection|Enumerable
    {
        $currentWorkspaceId = $this->getCurrentBlockAssetWorkspaceId($block);
        $readableWorkspaceIds = $this->getReadableBlockAssetWorkspaceIds($block);

        $filteredAssets = $assets->filter(function (BlockAsset $blockAsset) use ($containerKey, $blockOccurrence, $readableWorkspaceIds): bool {
            if (! in_array($blockAsset->workspace_id, $readableWorkspaceIds, true)) {
                return false;
            }

            if ((int) $blockAsset->occurrence !== $blockOccurrence) {
                return false;
            }

            if ($blockAsset->container === null) {
                return true;
            }

            if ($blockAsset->container !== $containerKey) {
                return false;
            }

            if ($blockAsset->pageable_type === null && $blockAsset->pageable_id === null) {
                return true;
            }

            if (! $this->inPageContext()) {
                return false;
            }

            return $blockAsset->pageable_type === $this->page->getMorphClass()
                && $blockAsset->pageable_id === $this->page->getKey();
        })->values();

        return $filteredAssets
            ->groupBy(fn (BlockAsset $blockAsset): string => implode(':', [
                $blockAsset->asset_type,
                $blockAsset->asset_id,
                $blockAsset->occurrence,
            ]))
            ->map(fn (SupportCollection $matchingAssets): BlockAsset => $matchingAssets
                ->first(fn (BlockAsset $blockAsset): bool => $blockAsset->workspace_id === $currentWorkspaceId)
                ?? $matchingAssets->first())
            ->sortBy(fn (BlockAsset $blockAsset): int => $blockAsset->order)
            ->values();
    }

    protected function blockAssetMatchesState(BlockAsset $asset, array $blockAssetData, string $containerKey, string $oldContainerKey, int $occurrence, Block $block): bool
    {
        if ((int) $asset->block_id !== (int) $block->getKey()) {
            return false;
        }

        if (isset($blockAssetData['block_id']) && (int) $blockAssetData['block_id'] !== (int) $asset->block_id) {
            return false;
        }

        if (isset($blockAssetData['asset_type']) && $blockAssetData['asset_type'] !== $asset->asset_type) {
            return false;
        }

        if (isset($blockAssetData['asset_id']) && $blockAssetData['asset_id'] !== $asset->asset_id) {
            return false;
        }

        if (! in_array($asset->workspace_id, $this->getReadableBlockAssetWorkspaceIds($block), true)) {
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

    protected function deleteRemovedBlockAssets(): void
    {
        foreach ($this->originalAssets as $containerKey => $originalBlockAssets) {
            foreach ($originalBlockAssets as $blockIndex => $originalAssets) {
                $currentAssets = $this->assets[$containerKey][$blockIndex] ?? [];

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

                    BlockAsset::query()
                        ->when(
                            isset($asset['id']),
                            fn (EloquentBuilder $query): EloquentBuilder => $query->whereKey((int) $asset['id']),
                            fn (EloquentBuilder $query): EloquentBuilder => $query->where([
                                'asset_id' => $asset['asset_id'],
                                'asset_type' => $asset['asset_type'],
                                'occurrence' => $asset['occurrence'],
                                'block_id' => $asset['original_block_id'],
                                'workspace_id' => (int) ($asset['workspace_id'] ?? $this->getCurrentBlockAssetWorkspaceId()),
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
