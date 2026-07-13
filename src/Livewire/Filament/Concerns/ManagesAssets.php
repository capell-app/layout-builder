<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use BackedEnum;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

trait ManagesAssets
{
    public function reorderAssets(string $containerKey, int $widgetIndex, int $index, int $newIndex): void
    {
        $this->assertCanUpdateLayout();

        $assets = $this->assets[$containerKey][$widgetIndex];

        $widgetAsset = $this->getWidgetAsset($containerKey, $widgetIndex, $index);

        throw_if($widgetAsset === null || $widgetAsset === [], Exception::class, sprintf('Asset %d not found for container: %s widget: %d', $index, $containerKey, $widgetIndex));

        unset($assets[$index]);

        $assets = array_values($assets);

        array_splice($assets, $newIndex, 0, [$widgetAsset]);

        $order = 1;
        $assets = array_map(
            function (array $asset) use (&$order): array {
                $asset['order'] = $order;
                $order++;

                return $asset;
            },
            $assets,
        );

        $this->assets[$containerKey][$widgetIndex] = $assets;

        $this->layoutUpdated();
    }

    public function moveAssetUp(string $containerKey, int $widgetIndex, int $assetIndex): void
    {
        if (! $this->canMoveAssetUp($containerKey, $widgetIndex, $assetIndex)) {
            return;
        }

        $this->reorderAssets($containerKey, $widgetIndex, $assetIndex, $assetIndex - 1);
    }

    public function moveAssetDown(string $containerKey, int $widgetIndex, int $assetIndex): void
    {
        if (! $this->canMoveAssetDown($containerKey, $widgetIndex, $assetIndex)) {
            return;
        }

        $this->reorderAssets($containerKey, $widgetIndex, $assetIndex, $assetIndex + 1);
    }

    public function canMoveAssetUp(string $containerKey, int $widgetIndex, int $assetIndex): bool
    {
        return $assetIndex > 0 && isset($this->assets[$containerKey][$widgetIndex][$assetIndex]);
    }

    public function canMoveAssetDown(string $containerKey, int $widgetIndex, int $assetIndex): bool
    {
        return isset($this->assets[$containerKey][$widgetIndex][$assetIndex + 1]);
    }

    public function hasPageAssets(string $containerKey, int $widgetIndex): bool
    {
        if (! $this->inPageContext()) {
            return false;
        }

        $assets = $this->getWidgetAssets($containerKey, $widgetIndex);

        if ($assets === []) {
            return false;
        }

        return collect($assets)
            ->contains(
                fn (array $asset): bool => isset($asset['pageable_type'], $asset['pageable_id'])
                    && $asset['pageable_type'] === $this->pageContext()->getMorphClass()
                    && $asset['pageable_id'] === $this->pageContext()->getKey(),
            );
    }

    public function widgetHasPageAssets(Widget $widget): bool
    {
        if (! $this->inPageContext()) {
            return $widget->assets()->whereNotNull('pageable_type')->whereNotNull('pageable_id')->exists();
        }

        if (property_exists($widget, 'page_assets_count')) {
            return $widget->page_assets_count > 0;
        }

        return $widget
            ->assets()
            ->where([
                'pageable_type' => $this->pageContext()->getMorphClass(),
                'pageable_id' => $this->pageContext()->getKey(),
            ])
            ->exists();
    }

    public function widgetHasGlobalAssets(Widget $widget): bool
    {
        if (property_exists($widget, 'global_assets_count')) {
            return $widget->global_assets_count > 0;
        }

        return $widget->assets()->whereNull(['pageable_type', 'pageable_id'])->exists();
    }

    public function selectAllAssets(string $containerKey, int $widgetIndex): void
    {
        $this->assertCanUpdateLayout();

        $this->selectedRecords[$containerKey][$widgetIndex] = $this->getAllSelectableAssetsKeys(
            $containerKey,
            $widgetIndex,
        );
    }

    public function deSelectAllAssets(string $containerKey, int $widgetIndex): void
    {
        $this->assertCanUpdateLayout();

        $this->selectedRecords[$containerKey][$widgetIndex] = [];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getWidgetAssetTypes(Widget $widget): array
    {
        return $this->getAllowedAssetTypes($widget);
    }

    public function getCurrentWidgetAssetWorkspaceId(?Widget $widget = null): int
    {
        if ($widget instanceof Widget && array_key_exists('workspace_id', $widget->getAttributes())) {
            return (int) $widget->getAttribute('workspace_id');
        }

        return $this->getCurrentWorkspaceId() ?? 0;
    }

    public function togglePageAssets(string $containerKey, int $widgetIndex, ?Pageable $page): void
    {
        $this->assertCanUpdateLayout();

        $hasPageAssets = $page instanceof Pageable;

        $this->updatePageAssets($containerKey, $widgetIndex, $hasPageAssets);

        $this->layoutUpdated();
    }

    public function shouldAddPageAssets(string $containerKey, int $widgetIndex): bool
    {
        if (! $this->inPageContext()) {
            return false;
        }

        $assets = $this->getWidgetAssets($containerKey, $widgetIndex);

        if ($assets === []) {
            return true;
        }

        return collect($assets)->contains(
            fn (array $widgetAsset): bool => $widgetAsset['pageable_id'] === $this->pageContext()->getKey()
                && $widgetAsset['pageable_type'] === $this->pageContext()->getMorphClass(),
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getWidgetAssets(string $containerKey, int $widgetIndex): array
    {
        return $this->assets[$containerKey][$widgetIndex];
    }

    public function countWidgetAssets(string $containerKey, int $widgetIndex): int
    {
        return count($this->getWidgetAssets($containerKey, $widgetIndex));
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getWidgetAsset(string $containerKey, int $widgetIndex, int $index): ?array
    {
        return $this->assets[$containerKey][$widgetIndex][$index] ?? null;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getWidgetAssetsByType(string $containerKey, int $widgetIndex, string $type): array
    {
        if (! isset($this->assets[$containerKey][$widgetIndex])) {
            return [];
        }

        return array_column(
            array_filter($this->assets[$containerKey][$widgetIndex], fn (array $widgetAsset): bool => $widgetAsset['asset_type'] === $type),
            'asset_id',
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getSelectedAssets(string $containerKey, int $widgetIndex): array
    {
        return $this->selectedRecords[$containerKey][$widgetIndex] ?? [];
    }

    public function removeSelectedAssets(string $containerKey, int $widgetIndex): void
    {
        $this->assertCanUpdateLayout();

        foreach ($this->selectedRecords[$containerKey][$widgetIndex] as $asset) {
            [$type, $uuid] = explode('.', (string) $asset);

            if (is_numeric($uuid)) {
                $uuid = (int) $uuid;
            }

            $this->removeAsset($containerKey, $widgetIndex, $uuid, $type);
        }

        $this->assets[$containerKey][$widgetIndex] = array_values($this->assets[$containerKey][$widgetIndex]);

        $this->selectedRecords[$containerKey][$widgetIndex] = [];

        $this->layoutUpdated();
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function updateWidgetAssetContentState(string $containerKey, int $widgetIndex, int $index, array $data): void
    {
        $this->assertCanEditContent();

        $widgetAsset = $this->assets[$containerKey][$widgetIndex][$index];

        $this->assets[$containerKey][$widgetIndex][$index] = array_replace_recursive($widgetAsset, $data);
    }

    /**
     * @return array<array-key, mixed>
     */
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

    public function reloadContainerWidgetAsset(string $containerKey, int $widgetIndex, int $index): void
    {
        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $assets = $widget->assets;
        $assets[$index] = $assets[$index]->fresh();
        $widget->setRelation('assets', $assets);
    }

    protected function moveContainerWidgetAssets(string $originalContainer, int $originalIndex, string $containerKey, int $widgetIndex): void
    {
        $widget = $this->assets[$originalContainer][$originalIndex];
        $widgetSelectedRecords = $this->selectedRecords[$originalContainer][$originalIndex] ?? [];

        $assets = $this->assets[$containerKey] ?? [];
        $assets = array_merge(array_slice($assets, 0, $widgetIndex), [$widget], array_slice($assets, $widgetIndex));
        $this->assets[$containerKey] = $assets;

        if ($containerKey !== $originalContainer) {
            unset($this->assets[$originalContainer][$originalIndex]);
            $this->assets[$originalContainer] = array_values($this->assets[$originalContainer]);
        }

        $selectedRecords = $this->selectedRecords[$containerKey] ?? [];
        $selectedRecords = array_merge(array_slice($selectedRecords, 0, $widgetIndex), [$widgetSelectedRecords], array_slice($selectedRecords, $widgetIndex));
        $this->selectedRecords[$containerKey] = $selectedRecords;

        if ($containerKey !== $originalContainer && isset($this->selectedRecords[$originalContainer][$originalIndex])) {
            unset($this->selectedRecords[$originalContainer][$originalIndex]);
            $this->selectedRecords[$originalContainer] = array_values($this->selectedRecords[$originalContainer]);
        }
    }

    protected function updatePageAssets(string $containerKey, int $widgetIndex, ?bool $hasPageAssets = null): void
    {
        if (! $this->assets[$containerKey][$widgetIndex]) {
            return;
        }

        if ($hasPageAssets === null) {
            $hasPageAssets = $this->hasPageAssets($containerKey, $widgetIndex);
        }

        foreach ($this->assets[$containerKey][$widgetIndex] as $assetIndex => $asset) {
            if ($hasPageAssets) {
                $this->assets[$containerKey][$widgetIndex][$assetIndex]['pageable_id'] = $this->pageContext()->getKey();
                $this->assets[$containerKey][$widgetIndex][$assetIndex]['pageable_type'] = $this->pageContext()->getMorphClass();
            } else {
                $this->assets[$containerKey][$widgetIndex][$assetIndex]['pageable_id'] = null;
                $this->assets[$containerKey][$widgetIndex][$assetIndex]['pageable_type'] = null;
            }
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function mapWidgetAssets(Widget $widget, string $containerKey, ?string $oldContainerKey = null): array
    {
        return $widget->assets->map(
            static function (WidgetAsset $widgetAsset) use ($containerKey, $oldContainerKey): array {
                $asset = [
                    'id' => $widgetAsset->id,
                    'widget_id' => $widgetAsset->widget_id,
                    'workspace_id' => $widgetAsset->workspace_id,
                    'asset_id' => $widgetAsset->asset_id,
                    'asset_type' => $widgetAsset->asset_type,
                    'meta' => $widgetAsset->meta,
                    'order' => $widgetAsset->order,
                    'occurrence' => $widgetAsset->occurrence,
                ];

                if ($widgetAsset->pageable_id !== null && $widgetAsset->pageable_type !== null) {
                    $asset['pageable_id'] = $widgetAsset->pageable_id;
                    $asset['pageable_type'] = $widgetAsset->pageable_type;
                    $asset['container'] = $containerKey;
                }

                if ($oldContainerKey !== null && $oldContainerKey !== '') {
                    $asset['old_container'] = $oldContainerKey;
                }

                return $asset;
            },
        )->all();
    }

    /**
     * @param  array<array-key, mixed>  $widgetAssets
     * @param  Collection<int, WidgetAsset>|null  $allWidgetAssets
     * @return Collection<int, WidgetAsset>
     */
    protected function setupWidgetAssets(string $containerKey, int $widgetIndex, array $widgetAssets, ?Collection $allWidgetAssets, Widget $widget): Collection
    {
        $assets = new Collection;

        if (! $allWidgetAssets instanceof Collection || $allWidgetAssets->isEmpty()) {
            return $assets;
        }

        /** @var SupportCollection<int, WidgetAsset> $allWidgetAssets */
        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        foreach ($widgetAssets as $widgetAssetData) {
            $widgetAssetData = is_array($widgetAssetData) ? $widgetAssetData : [];
            $type = $widgetAssetData['asset_type'];
            $assetId = is_numeric($widgetAssetData['asset_id']) ? (int) $widgetAssetData['asset_id'] : $widgetAssetData['asset_id'];

            $oldContainerKey = $widgetAssetData['old_container'] ?? $containerKey;

            /** @var ?WidgetAsset $matchingAsset */
            $matchingAsset = isset($widgetAssetData['id'])
                ? $allWidgetAssets->first(fn (WidgetAsset $asset): bool => $asset->getKey() === (int) $widgetAssetData['id']
                    && $this->widgetAssetMatchesState($asset, $widgetAssetData, $containerKey, $oldContainerKey, $occurrence, $widget))
                : null;

            $matchingAsset ??= $allWidgetAssets->first(function (WidgetAsset $asset) use ($type, $assetId, $oldContainerKey, $occurrence, $widget): bool {
                if ((int) $asset->widget_id !== (int) $widget->getKey()) {
                    return false;
                }

                if (! in_array($asset->workspace_id, $this->getReadableWidgetAssetWorkspaceIds($widget), true)) {
                    return false;
                }

                $matchesWidget = $asset->asset_type === $type
                    && $asset->asset_id === $assetId;

                if (! $matchesWidget) {
                    return false;
                }

                $matchesOccurrence = (int) $asset->occurrence === $occurrence;

                if (! $matchesOccurrence) {
                    return false;
                }

                if (! $this->inPageContext()) {
                    return $asset->pageable_type === null || $asset->pageable_id === null;
                }

                $matchesPage = $asset->pageable_type === $this->pageContext()->getMorphClass()
                    && $asset->pageable_id === $this->pageContext()->getKey();

                $matchesContainer = $asset->container === null || $asset->container === $oldContainerKey;

                return $matchesPage && $matchesContainer;
            });

            if ($matchingAsset === null) {
                continue;
            }

            $orderValue = $widgetAssetData['order'] ?? null;
            $occurrenceValue = $widgetAssetData['occurrence'] ?? null;
            $pageableIdValue = $widgetAssetData['pageable_id'] ?? null;
            $pageableTypeValue = $widgetAssetData['pageable_type'] ?? null;

            $widgetAsset = clone $matchingAsset;
            $widgetAsset->order = is_numeric($orderValue) ? max(0, (int) $orderValue) : $widgetAsset->order;
            $widgetAsset->occurrence = max(0, is_numeric($occurrenceValue) ? (int) $occurrenceValue : $occurrence);
            $widgetAsset->pageable_id = is_numeric($pageableIdValue) ? max(0, (int) $pageableIdValue) : null;
            $widgetAsset->pageable_type = is_string($pageableTypeValue) ? $pageableTypeValue : null;

            $assets->push($widgetAsset);
        }

        return $assets;
    }

    protected function setupSelectedAssets(): void
    {
        $this->selectedRecords = [];

        foreach (array_keys($this->containers ?? []) as $containerKey) {
            $this->selectedRecords[$containerKey] = [];

            foreach ($this->containerWidgets((string) $containerKey) as $widgetIndex => $widget) {
                $this->selectedRecords[$containerKey][$widgetIndex] = [];
            }
        }
    }

    protected function saveOriginalAssets(): void
    {
        $originalAssets = [];

        foreach ($this->assets as $containerKey => $containerWidgets) {
            foreach ($containerWidgets as $widgetIndex => $widgetAssets) {
                $containerWidget = $this->getContainerWidget($containerKey, $widgetIndex);

                foreach ($widgetAssets as $widgetAssetIndex => $widgetAsset) {
                    $widgetAsset = is_array($widgetAsset) ? $widgetAsset : [];
                    $widgetAsset['original_container_key'] = $containerKey;
                    $widgetAsset['original_widget_id'] = $containerWidget->id;
                    $widgetAsset['original_widget_key'] = $containerWidget->key;

                    $originalAssets[$containerKey][$widgetIndex][$widgetAssetIndex] = $widgetAsset;
                }
            }
        }

        $this->originalAssets = $originalAssets;
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getAllSelectableAssetsKeys(string $containerKey, int $widgetIndex): array
    {
        $containerAssets = $this->assets[$containerKey] ?? null;
        $widgetAssets = is_array($containerAssets) ? ($containerAssets[$widgetIndex] ?? null) : null;

        return array_values(array_map(
            function (mixed $widgetAsset): string {
                if (! is_array($widgetAsset)) {
                    return '';
                }

                $assetType = $widgetAsset['asset_type'] ?? null;
                $assetId = $widgetAsset['asset_id'] ?? null;

                return sprintf(
                    '%s.%s',
                    is_scalar($assetType) ? (string) $assetType : '',
                    is_scalar($assetId) ? (string) $assetId : '',
                );
            },
            is_array($widgetAssets) ? $widgetAssets : [],
        ));
    }

    /**
     * @param  array<array-key, mixed>  $assetsMeta
     */
    protected function addAssets(string $containerKey, int $widgetIndex, ?bool $hasPageAssets, string $type, mixed $assets, array $assetsMeta = []): void
    {
        $this->assertCanUpdateLayout();

        if (! isset($this->assets[$containerKey][$widgetIndex])) {
            $this->assets[$containerKey][$widgetIndex] = [];
        }

        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $validatedAssetIds = $this->getValidatedAssetIds($widget, $type, $assets);

        if ($validatedAssetIds === []) {
            return;
        }

        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        $order = $this->countWidgetAssets($containerKey, $widgetIndex);

        $hasPageAssets = $hasPageAssets === true;

        foreach ($validatedAssetIds as $assetId) {
            $order++;

            $meta = $assetsMeta[$assetId] ?? [];

            $asset = [
                'asset_id' => $assetId,
                'asset_type' => $type,
                'meta' => $meta,
                'widget_id' => $widget->id,
                'order' => $order,
                'occurrence' => $occurrence,
            ];

            if ($hasPageAssets) {
                $asset['pageable_id'] = $this->pageContext()->getKey();
                $asset['pageable_type'] = $this->pageContext()->getMorphClass();
                $asset['container'] = $containerKey;
            }

            $this->assets[$containerKey][$widgetIndex][] = $asset;

            $widgetAsset = $this->addWidgetAsset(
                widget: $widget,
                containerKey: $containerKey,
                type: $type,
                hasPageAssets: $hasPageAssets,
                assetId: $assetId,
                meta: $meta,
                occurrence: $occurrence,
                order: $order,
            );

            $widgetAsset->setRelation('widget', $widget);

            $widget->assets->add($widgetAsset);
        }

        $widget->assets->load([
            'asset' => fn (MorphTo $query): MorphTo => $query->morphWith($this->getAssetRelations()),
        ]);

        $this->containerWidgets[$containerKey][$widgetIndex] = $widget;
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getValidatedAssetIds(Widget $widget, string $type, mixed $assetIds): array
    {
        $normalizedType = $this->normalizeAssetType($type);

        if (! in_array($normalizedType, $this->getAllowedAssetTypes($widget), true)) {
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

    /**
     * @return array<array-key, mixed>
     */
    protected function getAllowedAssetTypes(Widget $widget): array
    {
        $blueprint = $widget->relationLoaded('blueprint') ? $widget->getRelation('blueprint') : null;
        $assetTypes = isset($widget->admin['asset_types']) && $widget->admin['asset_types'] !== []
            ? $widget->admin['asset_types']
            : ($blueprint instanceof Blueprint ? ($blueprint->admin['asset_types'] ?? []) : []);

        if ($assetTypes === []) {
            return CapellCore::getAssets()
                ->keys()
                ->map(fn (string $assetType): string => $this->normalizeAssetType($assetType))
                ->values()
                ->all();
        }

        $registeredAssetTypes = [];

        foreach ($assetTypes as $assetType) {
            $normalizedAssetType = $this->normalizeAssetType($assetType);

            if ($this->hasRegisteredAssetType($normalizedAssetType)) {
                $registeredAssetTypes[] = $normalizedAssetType;
            }
        }

        return $registeredAssetTypes;
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

    /**
     * @param  Builder<Model>  $query
     */
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
            $query->whereKeyNot($this->pageContext()->getKey());
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
    protected function getReadableWidgetAssetWorkspaceIds(?Widget $widget = null): array
    {
        $workspaceId = $this->getCurrentWidgetAssetWorkspaceId($widget);

        if ($workspaceId === 0) {
            return [0];
        }

        return [$workspaceId, 0];
    }

    /**
     * @return array<int>
     */
    protected function getCurrentContainerWidgetAssetWorkspaceIds(): array
    {
        $workspaceIds = Widget::query()
            ->whereIn('key', $this->getContainerWidgetKeys())
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
    protected function getCurrentContainerWidgetIds(): array
    {
        return Widget::query()
            ->whereIn('key', $this->getContainerWidgetKeys())
            ->pluck('id')
            ->map(fn (mixed $widgetId): int => (int) $widgetId)
            ->all();
    }

    protected function updateAssets(string $containerKey, int $widgetIndex, ?string $oldContainerKey = null): void
    {
        $oldContainerKey ??= $containerKey;

        $assets = $this->assets[$containerKey][$widgetIndex] ?? [];

        $widget = $this->getContainerWidget($containerKey, $widgetIndex);

        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        $widgetHasPageAssets = $assets !== [] ? $this->widgetHasPageAssets($widget) : $this->inPageContext();

        $hasPageAssets = $assets !== [] ? $this->hasPageAssets($containerKey, $widgetIndex) : $this->inPageContext();

        $assetIds = new SupportCollection(array_values(array_unique(array_filter(
            array_map(
                fn (array $asset): int => isset($asset['id']) && (is_int($asset['id']) || is_string($asset['id'])) ? (int) $asset['id'] : 0,
                $assets,
            ),
            fn (int $assetId): bool => $assetId > 0,
        ))));

        $existingAssets = $widget->assets()
            ->where('workspace_id', $this->getCurrentWidgetAssetWorkspaceId($widget))
            ->where(
                fn (EloquentBuilder $query): EloquentBuilder => $query
                    ->where(
                        fn (EloquentBuilder $query): EloquentBuilder => $query
                            ->where('occurrence', $occurrence)
                            ->when(
                                $widgetHasPageAssets ? fn (EloquentBuilder $query) => $query
                                    ->where([
                                        'container' => $oldContainerKey,
                                        'pageable_type' => $this->pageContext()->getMorphClass(),
                                        'pageable_id' => $this->pageContext()->getKey(),
                                    ]) : null,
                                fn (EloquentBuilder $query) => $query->whereNull(['container', 'pageable_id', 'pageable_type']),
                            ),
                    )
                    ->when(
                        $assetIds->isNotEmpty(),
                        function (EloquentBuilder $query) use ($assetIds): EloquentBuilder {
                            $model = $query->getModel();
                            $qualifiedKeyName = $model->getTable() . '.' . $model->getKeyName();

                            return $query->orWhereIn($qualifiedKeyName, $assetIds->all());
                        },
                    ),
            )
            ->get();

        $existingAssetsByKey = $existingAssets
            ->mapWithKeys(fn (WidgetAsset $widgetAsset): array => [$widgetAsset->asset_key => $widgetAsset]);

        $existingAssetsById = $existingAssets
            ->keyBy(fn (WidgetAsset $widgetAsset): int => $widgetAsset->getKey());

        if ($existingAssets->isNotEmpty()) {
            $activeWidgetAssetIds = $this->activeWidgetAssetIds($widget);

            $currentAssetKeys = [];

            foreach ($assets as $widgetAsset) {
                $key = sprintf('%s.%s', $widgetAsset['asset_type'], $widgetAsset['asset_id']);

                if ($existingAssetsByKey->has($key)) {
                    $currentAssetKeys[$key] = true;
                }
            }

            $assetsToRemove = $currentAssetKeys !== []
                ? $existingAssetsByKey->diffKeys($currentAssetKeys)
                : $existingAssetsByKey;

            $assetsToRemove = $assetsToRemove->reject(
                fn (WidgetAsset $widgetAsset): bool => in_array((int) $widgetAsset->getKey(), $activeWidgetAssetIds, true),
            );

            if ($assetsToRemove->isNotEmpty()) {
                $assetsToRemove->each(function (WidgetAsset $widgetAsset) use ($containerKey, $widgetIndex, $widget): void {
                    $searchIndex = $widget->assets->search(fn (Model $asset): bool => $asset instanceof WidgetAsset && $asset->id === $widgetAsset->id);
                    if (is_int($searchIndex)) {
                        $widget->assets->forget([$searchIndex]);
                    }

                    $this->removeAsset($containerKey, $widgetIndex, $widgetAsset->asset_id, $widgetAsset->asset_type);

                    $widgetAsset->delete();
                });
            }
        }

        if ($assets === []) {
            return;
        }

        foreach ($assets as $widgetAsset) {
            $widgetAsset = is_array($widgetAsset) ? $widgetAsset : [];
            $assetType = is_scalar($widgetAsset['asset_type'] ?? null) ? $widgetAsset['asset_type'] : '';
            $assetIdValue = is_scalar($widgetAsset['asset_id'] ?? null) ? $widgetAsset['asset_id'] : '';
            $key = sprintf('%s.%s', $assetType, $assetIdValue);

            $order = $widgetAsset['order'] ?? 0;

            $existingAsset = isset($widgetAsset['id'])
                ? $existingAssetsById->get((int) $widgetAsset['id'])
                : null;

            if (! $existingAsset instanceof WidgetAsset) {
                $existingAsset = $existingAssetsByKey->get($key);
            }

            if ($existingAsset instanceof WidgetAsset) {
                $metaValue = $widgetAsset['meta'] ?? [];
                $existingAsset->order = max(0, is_numeric($order) ? (int) $order : 0);
                $existingAsset->meta = is_array($metaValue) ? $metaValue : [];
                $existingAsset->occurrence = max(0, $occurrence);

                if ($hasPageAssets) {
                    $pageableKey = $this->pageContext()->getKey();
                    $existingAsset->container = $containerKey;
                    $existingAsset->pageable_id = is_numeric($pageableKey) ? max(0, (int) $pageableKey) : null;
                    $existingAsset->pageable_type = $this->pageContext()->getMorphClass();
                } else {
                    $existingAsset->container = null;
                    $existingAsset->pageable_id = null;
                    $existingAsset->pageable_type = null;
                }

                $existingAsset->save();

                continue;
            }

            $this->createWidgetAsset(
                widget: $widget,
                containerKey: $containerKey,
                occurrence: $occurrence,
                hasPageAssets: $hasPageAssets,
                order: $order,
                asset: $widgetAsset,
            );
        }
    }

    /**
     * @return array<int>
     */
    protected function activeWidgetAssetIds(Widget $widget): array
    {
        $assetIds = [];

        foreach ($this->currentWidgetAssetData() as $widgetAsset) {
            if (! isset($widgetAsset['id'])) {
                continue;
            }

            $assetId = (int) $widgetAsset['id'];

            if ($assetId > 0) {
                $assetIds[] = $assetId;
            }
        }

        return array_values(array_unique($assetIds));
    }

    /**
     * @return array<int, array<array-key, mixed>>
     */
    protected function currentWidgetAssetData(): array
    {
        $widgetAssets = [];

        foreach ($this->assets as $containerWidgets) {
            foreach ($containerWidgets as $containerWidgetAssets) {
                foreach ($containerWidgetAssets as $widgetAsset) {
                    if (is_array($widgetAsset)) {
                        $widgetAssets[] = $widgetAsset;
                    }
                }
            }
        }

        return $widgetAssets;
    }

    /**
     * @param  array<array-key, mixed>  $meta
     */
    protected function addWidgetAsset(
        Widget $widget,
        string $containerKey,
        string $type,
        bool $hasPageAssets,
        int|string $assetId,
        array $meta,
        int $occurrence,
        int $order,
    ): WidgetAsset {
        $pageKey = $hasPageAssets ? $this->pageContext()->getKey() : null;
        $pageId = is_numeric($pageKey) ? max(0, (int) $pageKey) : null;

        $widgetAsset = $widget->assets
            ->where([
                'asset_id' => $assetId,
                'asset_type' => $type,
                'occurrence' => $occurrence,
            ])
            ->when(
                $pageId,
                fn (SupportCollection $collection): SupportCollection => $collection
                    ->where('container', $containerKey)
                    ->where('pageable_id', $pageId)
                    ->where('pageable_type', $this->pageContext()->getMorphClass()),
            )
            ->first();

        if (! $widgetAsset instanceof WidgetAsset) {
            /** @var WidgetAsset $widgetAsset */
            $widgetAsset = $widget->assets()->newModelInstance([
                'meta' => $meta,
                'order' => $order,
                'widget_id' => $widget->id,
                'workspace_id' => $this->getCurrentWidgetAssetWorkspaceId($widget),
                'asset_type' => mb_strtolower($type),
                'asset_id' => $assetId,
                'occurrence' => $occurrence,
            ]);

            if ($pageId !== null) {
                $widgetAsset->pageable_id = $pageId;
                $widgetAsset->pageable_type = $this->pageContext()->getMorphClass();
                $widgetAsset->container = $containerKey;
            }
        }

        return $widgetAsset;
    }

    /**
     * @param  array<array-key, mixed>  $asset
     */
    protected function createWidgetAsset(
        Widget $widget,
        string $containerKey,
        int $occurrence,
        bool $hasPageAssets,
        int $order,
        array $asset,
    ): WidgetAsset {
        $attributes = [
            'widget_id' => $widget->id,
            'workspace_id' => $this->getCurrentWidgetAssetWorkspaceId($widget),
            'asset_type' => $asset['asset_type'],
            'asset_id' => $asset['asset_id'],
            'occurrence' => $occurrence,
        ];

        if ($hasPageAssets) {
            $attributes['pageable_id'] = $this->pageContext()->getKey();
            $attributes['pageable_type'] = $this->pageContext()->getMorphClass();
            $attributes['container'] = $containerKey;
        } else {
            $attributes['pageable_id'] = null;
            $attributes['pageable_type'] = null;
            $attributes['container'] = null;
        }

        /** @var WidgetAsset|null $existing */
        $existing = WidgetAsset::query()
            ->where($attributes)
            ->first();

        if ($existing instanceof WidgetAsset) {
            $existingMeta = $asset['meta'] ?? [];
            $existing->order = max(0, $order);
            $existing->meta = is_array($existingMeta) ? $existingMeta : [];
            $existing->save();

            return $existing;
        }

        /** @var WidgetAsset $widgetAsset */
        $widgetAsset = $widget->assets()->make(array_merge([
            'meta' => $asset['meta'] ?? [],
            'order' => $order,
        ], $attributes));

        $widgetAsset->save();

        return $widgetAsset;
    }

    protected function removeAsset(string $containerKey, int $widgetIndex, mixed $uuid, string $type): void
    {
        foreach ($this->assets[$containerKey][$widgetIndex] as $index => $widgetAsset) {
            if ($widgetAsset['asset_id'] !== $uuid) {
                continue;
            }

            if ($widgetAsset['asset_type'] !== $type) {
                continue;
            }

            unset($this->assets[$containerKey][$widgetIndex][$index]);
        }
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    protected function updateWidgetAsset(string $containerKey, int $widgetIndex, int $index, array $data): void
    {
        $this->assertCanUpdateLayout();

        $widgetAsset = $this->assets[$containerKey][$widgetIndex][$index];

        $this->assets[$containerKey][$widgetIndex][$index] = array_merge_recursive($widgetAsset, $data);
    }

    /**
     * @return Collection<int, WidgetAsset>
     */
    protected function loadWidgetAssets(Widget $widget, string $containerKey, int $widgetOccurrence): Collection
    {
        /** @var class-string<WidgetAsset> $model */
        $model = WidgetAsset::class;

        $assets = $model::query()
            ->with([
                'asset' => function (Relation $query): Relation {
                    if ($query instanceof MorphTo) {
                        $query->morphWith($this->getAssetRelations());
                    }

                    return $query;
                },
                'media',
            ])
            ->where('widget_id', $widget->id)
            ->whereIn('workspace_id', $this->getReadableWidgetAssetWorkspaceIds($widget))
            ->where('occurrence', $widgetOccurrence)
            ->where(
                fn (EloquentBuilder $query): EloquentBuilder => $query->where('container', $containerKey)
                    ->orWhereNull('container'),
            )
            ->when(
                $this->page,
                fn (EloquentBuilder $query): EloquentBuilder => $query->where(
                    fn (EloquentBuilder $query): EloquentBuilder => $query->where([
                        'pageable_type' => $this->pageContext()->getMorphClass(),
                        'pageable_id' => $this->pageContext()->getKey(),
                    ])
                        ->orWhereNull(['pageable_type', 'pageable_id']),
                ),
                fn (EloquentBuilder $query): EloquentBuilder => $query->orWhereNull(['pageable_type', 'pageable_id']),
            )
            ->ordered()
            ->get()
            ->each->setRelation('widget', $widget);

        return $this->filterContainerWidgetAssets($assets, $containerKey, $widgetOccurrence, $widget);
    }

    /**
     * @return Collection<int, WidgetAsset>
     */
    protected function loadWidgetAssetsFor(Widget $widget, string $containerKey, int $widgetIndex): Collection
    {
        $occurrence = $this->getContainerWidgetOccurrence($containerKey, $widgetIndex);

        $widgetAssets = new SupportCollection($this->assets[$containerKey][$widgetIndex] ?? []);

        if ($widgetAssets->isEmpty()) {
            return new Collection;
        }

        $existingIds = $widgetAssets
            ->filter(fn (array $asset): bool => isset($asset['id']))
            ->pluck('id')
            ->all();

        $newAssets = $widgetAssets
            ->reject(fn (array $asset): bool => isset($asset['id']))
            ->all();

        $assets = $this->buildPreloadedWidgetAssets($existingIds, $newAssets);

        return $this->filterContainerWidgetAssets($assets, $containerKey, $occurrence, $widget)
            ->each(function (WidgetAsset $asset) use ($widget): void {
                $asset->setRelation('widget', $widget);
            });
    }

    /**
     * @return Collection<int, WidgetAsset>|null
     */
    protected function preloadAllWidgetAssets(): ?Collection
    {
        $widgetAssets = new SupportCollection($this->currentWidgetAssetData());

        if ($widgetAssets->isEmpty()) {
            return null;
        }

        $existingIds = $widgetAssets
            ->filter(fn (array $asset): bool => isset($asset['id']))
            ->pluck('id')
            ->all();

        $newAssets = $widgetAssets
            ->reject(fn (array $asset): bool => isset($asset['id']))
            ->all();

        return $this->buildPreloadedWidgetAssets($existingIds, $newAssets);
    }

    /**
     * @param  array<array-key, mixed>  $existingIds
     * @param  array<array-key, mixed>  $newAssets
     * @return Collection<int, WidgetAsset>
     */
    protected function buildPreloadedWidgetAssets(array $existingIds, array $newAssets): Collection
    {
        /** @var class-string<WidgetAsset> $model */
        $model = WidgetAsset::class;

        $existingAssets = $existingIds === []
            ? (new $model)->newCollection()
            : $model::query()
                ->whereKey($existingIds)
                ->whereIn('widget_id', $this->getCurrentContainerWidgetIds())
                ->whereIn('workspace_id', $this->getCurrentContainerWidgetAssetWorkspaceIds())
                ->when(
                    $this->page,
                    fn (EloquentBuilder $query) => $query->where(
                        fn (EloquentBuilder $query) => $query->where([
                            'pageable_type' => $this->pageContext()->getMorphClass(),
                            'pageable_id' => $this->pageContext()->getKey(),
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
            ->filter(fn (array $data): bool => in_array((int) ($data['workspace_id'] ?? $this->getCurrentWidgetAssetWorkspaceId()), $this->getCurrentContainerWidgetAssetWorkspaceIds(), true))
            ->map(function (mixed $data) use ($model): WidgetAsset {
                $widgetAsset = $model::query()->newModelInstance();
                $widgetAsset->forceFill(is_array($data) ? $data : []);

                return $widgetAsset;
            });

        $allAssets = (new $model)->newCollection(array_merge($existingAssets->all(), $newAssetsCollection->all()));

        $eloquentCollection = new Collection($allAssets->all());

        return $eloquentCollection->load([
            'asset' => function (Relation $query): Relation {
                if ($query instanceof MorphTo) {
                    $query->morphWith($this->getAssetRelations());
                }

                return $query;
            },
        ])
            ->filter(fn (WidgetAsset $widgetAsset): bool => $widgetAsset->asset instanceof Model && $this->canUseAssetRecord($widgetAsset->asset));
    }

    /**
     * @param  Collection<int, WidgetAsset>  $assets
     * @return Collection<int, WidgetAsset>
     */
    protected function filterContainerWidgetAssets(Collection $assets, string $containerKey, int $widgetOccurrence, ?Widget $widget = null): Collection
    {
        $currentWorkspaceId = $this->getCurrentWidgetAssetWorkspaceId($widget);
        $readableWorkspaceIds = $this->getReadableWidgetAssetWorkspaceIds($widget);

        $filteredAssets = $assets->filter(function (WidgetAsset $widgetAsset) use ($containerKey, $widgetOccurrence, $readableWorkspaceIds): bool {
            if (! in_array($widgetAsset->workspace_id, $readableWorkspaceIds, true)) {
                return false;
            }

            if ((int) $widgetAsset->occurrence !== $widgetOccurrence) {
                return false;
            }

            if ($widgetAsset->container === null) {
                return true;
            }

            if ($widgetAsset->container !== $containerKey) {
                return false;
            }

            if ($widgetAsset->pageable_type === null && $widgetAsset->pageable_id === null) {
                return true;
            }

            if (! $this->inPageContext()) {
                return false;
            }

            return $widgetAsset->pageable_type === $this->pageContext()->getMorphClass()
                && $widgetAsset->pageable_id === $this->pageContext()->getKey();
        })->values();

        $selectedAssets = $filteredAssets
            ->groupBy(fn (WidgetAsset $widgetAsset): string => implode(':', [
                $widgetAsset->asset_type,
                $widgetAsset->asset_id,
                $widgetAsset->occurrence,
            ]))
            ->map(function (SupportCollection $matchingAssets) use ($currentWorkspaceId): WidgetAsset {
                $workspaceAsset = $matchingAssets->first(
                    fn (WidgetAsset $widgetAsset): bool => $widgetAsset->workspace_id === $currentWorkspaceId,
                );

                if ($workspaceAsset instanceof WidgetAsset) {
                    return $workspaceAsset;
                }

                $firstAsset = $matchingAssets->first();

                throw_unless($firstAsset instanceof WidgetAsset, RuntimeException::class, 'Expected a widget asset while filtering container assets.');

                return $firstAsset;
            })
            ->sortBy(fn (WidgetAsset $widgetAsset): int => $widgetAsset->order)
            ->values();

        return new Collection($selectedAssets->all());
    }

    /**
     * @param  array<array-key, mixed>  $widgetAssetData
     */
    protected function widgetAssetMatchesState(WidgetAsset $asset, array $widgetAssetData, string $containerKey, string $oldContainerKey, int $occurrence, Widget $widget): bool
    {
        if ((int) $asset->widget_id !== (int) $widget->getKey()) {
            return false;
        }

        if (isset($widgetAssetData['widget_id']) && (int) $widgetAssetData['widget_id'] !== (int) $asset->widget_id) {
            return false;
        }

        if (isset($widgetAssetData['asset_type']) && $widgetAssetData['asset_type'] !== $asset->asset_type) {
            return false;
        }

        if (isset($widgetAssetData['asset_id']) && (string) $widgetAssetData['asset_id'] !== (string) $asset->asset_id) {
            return false;
        }

        if (! in_array($asset->workspace_id, $this->getReadableWidgetAssetWorkspaceIds($widget), true)) {
            return false;
        }

        if ($asset->container !== null && ! in_array($asset->container, [$containerKey, $oldContainerKey], true)) {
            return false;
        }

        if (! $this->inPageContext()) {
            return $asset->pageable_type === null && $asset->pageable_id === null;
        }

        return ($asset->pageable_type === null && $asset->pageable_id === null)
            || ($asset->pageable_type === $this->pageContext()->getMorphClass()
                && $asset->pageable_id === $this->pageContext()->getKey());
    }

    protected function deleteRemovedWidgetAssets(): void
    {
        foreach ($this->originalAssets ?? [] as $containerKey => $originalWidgetAssets) {
            foreach ($originalWidgetAssets as $widgetIndex => $originalAssets) {
                $currentAssets = $this->assets[$containerKey][$widgetIndex] ?? [];

                $originalKeys = array_values(array_map(
                    static fn (array $asset): string => $asset['asset_type'] . ':' . $asset['asset_id'] . ':' . $asset['occurrence'] . ':' . $asset['original_container_key'],
                    $originalAssets,
                ));

                $currentKeys = array_values(array_map(
                    static fn (array $asset): string => $asset['asset_type'] . ':' . $asset['asset_id'] . ':' . $asset['occurrence'] . ':' . $containerKey,
                    $currentAssets,
                ));

                $removedKeys = array_diff($originalKeys, $currentKeys);

                if ($removedKeys === []) {
                    continue;
                }

                $hasPageAssets = false;
                if ($this->inPageContext()) {
                    foreach ($originalAssets as $asset) {
                        if ($asset['pageable_id'] === $this->pageContext()->getKey() && $asset['pageable_type'] === $this->pageContext()->getMorphClass()) {
                            $hasPageAssets = true;

                            break;
                        }
                    }
                }

                foreach ($originalAssets as $asset) {
                    $asset = is_array($asset) ? $asset : [];
                    $key = $asset['asset_type'] . ':' . $asset['asset_id'] . ':' . $asset['occurrence'] . ':' . $asset['original_container_key'];
                    if (! in_array($key, $removedKeys, true)) {
                        continue;
                    }

                    WidgetAsset::query()
                        ->when(
                            isset($asset['id']),
                            fn (EloquentBuilder $query): EloquentBuilder => $query->whereKey((int) $asset['id']),
                            fn (EloquentBuilder $query): EloquentBuilder => $query->where([
                                'asset_id' => $asset['asset_id'],
                                'asset_type' => $asset['asset_type'],
                                'occurrence' => $asset['occurrence'],
                                'widget_id' => $asset['original_widget_id'],
                                'workspace_id' => (int) ($asset['workspace_id'] ?? $this->getCurrentWidgetAssetWorkspaceId()),
                            ]),
                        )
                        ->when(
                            $hasPageAssets,
                            fn (EloquentBuilder $query) => $query->where([
                                'container' => $asset['original_container_key'],
                                'pageable_type' => $this->pageContext()->getMorphClass(),
                                'pageable_id' => $this->pageContext()->getKey(),
                            ]),
                        )
                        ->delete();
                }
            }
        }
    }
}
