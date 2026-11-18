<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Stores the widgets of unsaved editor state as this request's container widgets, so a preview
 * render shows them in place of the saved ones. The widget host clears them after the render.
 */
final class RegisterPreviewWidgetsAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<string, mixed>  $containers
     * @param  array<string, mixed>  $assets  keyed by container, then the widget's index in its normalised widget list
     */
    public function handle(Page $page, Language $language, array $containers, array $assets = []): bool
    {
        $widgetKeys = $this->widgetKeys($containers);

        if ($widgetKeys === []) {
            return false;
        }

        /** @var Collection<string, Widget> $blocks */
        $blocks = Widget::query()
            ->whereIn('key', $widgetKeys)
            ->with([
                'blueprint',
                'media' => fn (BuilderContract $query): BuilderContract => $query->ordered(),
                'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->getKey()),
            ])
            ->get()
            ->keyBy('key');

        if ($blocks->isEmpty()) {
            return false;
        }

        CapellLayoutManager::clearContainerWidgets();

        foreach ($containers as $containerKey => $container) {
            if (! is_array($container)) {
                continue;
            }

            foreach (LayoutWidgetData::normalizeMany($container['widgets'] ?? []) as $widgetIndex => $widgetData) {
                $widgetKey = LayoutWidgetData::key($widgetData);

                if ($widgetKey === null) {
                    continue;
                }

                $block = $blocks->get($widgetKey);

                if (! $block instanceof Widget) {
                    continue;
                }

                $previewBlock = clone $block;
                $translation = $previewBlock->translation;

                if ($translation instanceof Model) {
                    $translation->setRelation('language', $language);
                }
                $previewBlockMedia = $previewBlock->media;
                $previewBlock->setRelation('image', $previewBlockMedia->firstWhere('type', MediaCollectionEnum::Image->value));
                $previewBlock->setRelation(
                    'backgroundImage',
                    $previewBlock->media->firstWhere('type', MediaCollectionEnum::BackgroundImage->value),
                );
                $previewBlock->setRelation(
                    'assets',
                    $this->previewBlockAssets(
                        page: $page,
                        block: $previewBlock,
                        containerKey: (string) $containerKey,
                        occurrence: LayoutWidgetData::occurrence($widgetData),
                        assetState: $this->assetState($assets, (string) $containerKey, $widgetIndex),
                    ),
                );

                CapellLayoutManager::storeContainerWidget(
                    (string) $containerKey,
                    $widgetKey,
                    $previewBlock,
                    LayoutWidgetData::occurrence($widgetData),
                );
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $assets
     * @return array<int, mixed>
     */
    private function assetState(array $assets, string $containerKey, int|string $widgetIndex): array
    {
        $container = $assets[$containerKey] ?? null;
        $state = is_array($container) ? ($container[$widgetIndex] ?? null) : null;

        return is_array($state) ? $state : [];
    }

    /**
     * @param  array<string, mixed>  $containers
     * @return array<int, string>
     */
    private function widgetKeys(array $containers): array
    {
        return collect($containers)
            ->filter(fn (mixed $container): bool => is_array($container))
            ->flatMap(fn (array $container): array => LayoutWidgetData::normalizeMany($container['widgets'] ?? []))
            ->map(static fn (array $widgetData): ?string => LayoutWidgetData::key($widgetData))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $assetState
     * @return Collection<int, WidgetAsset>
     */
    private function previewBlockAssets(Page $page, Widget $block, string $containerKey, int $occurrence, array $assetState): Collection
    {
        if ($assetState === []) {
            return collect();
        }

        $existingAssets = $this->existingBlockAssets($assetState);
        $targetModels = $this->targetModels($assetState);

        return collect($assetState)
            ->filter(fn (mixed $asset): bool => is_array($asset))
            ->map(function (array $asset) use ($page, $block, $containerKey, $occurrence, $existingAssets, $targetModels): WidgetAsset {
                $blockAsset = isset($asset['id']) && is_numeric($asset['id'])
                    ? $existingAssets->get((int) $asset['id'])
                    : null;

                $blockAsset = $blockAsset instanceof WidgetAsset
                    ? clone $blockAsset
                    : $this->newBlockAsset($block);

                $blockAsset->forceFill([
                    'block_id' => $block->getKey(),
                    'container' => $asset['container'] ?? $containerKey,
                    'workspace_id' => $asset['workspace_id'] ?? null,
                    'pageable_type' => $asset['pageable_type'] ?? $page->getMorphClass(),
                    'pageable_id' => $asset['pageable_id'] ?? $page->getKey(),
                    'asset_type' => $asset['asset_type'] ?? null,
                    'asset_id' => $asset['asset_id'] ?? null,
                    'meta' => $asset['meta'] ?? null,
                    'occurrence' => $asset['occurrence'] ?? $occurrence,
                    'order' => $asset['order'] ?? 0,
                ]);

                $targetModel = $this->targetModel($targetModels, $blockAsset->asset_type, $blockAsset->asset_id);

                if ($targetModel instanceof Model) {
                    $blockAsset->setRelation('asset', $targetModel);
                }

                return $blockAsset;
            })
            ->filter()
            ->sortBy(fn (WidgetAsset $blockAsset): int => (int) $blockAsset->order)
            ->values();
    }

    private function newBlockAsset(Widget $block): WidgetAsset
    {
        $blockAsset = $block->assets()->make();

        throw_unless($blockAsset instanceof WidgetAsset);

        return $blockAsset;
    }

    /**
     * @param  array<int, mixed>  $assetState
     * @return Collection<int, WidgetAsset>
     */
    private function existingBlockAssets(array $assetState): Collection
    {
        $ids = collect($assetState)
            ->filter(fn (mixed $asset): bool => is_array($asset) && isset($asset['id']) && is_numeric($asset['id']))
            ->map(static fn (array $asset): int => (int) $asset['id'])
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return WidgetAsset::query()
            ->with(['asset', 'media'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  array<int, mixed>  $assetState
     * @return array<string, Collection<int|string, Model>>
     */
    private function targetModels(array $assetState): array
    {
        $idsByType = [];

        foreach ($assetState as $asset) {
            if (! is_array($asset)) {
                continue;
            }

            if (! isset($asset['asset_type'], $asset['asset_id'])) {
                continue;
            }

            $type = (string) $asset['asset_type'];
            $idsByType[$type] ??= [];
            $idsByType[$type][] = $asset['asset_id'];
        }

        $models = [];

        foreach ($idsByType as $type => $ids) {
            $class = Relation::getMorphedModel($type) ?? $type;
            if (! is_string($class)) {
                continue;
            }

            if (! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, Model::class)) {
                continue;
            }

            /** @var class-string<Model> $class */
            $models[$type] = $class::query()
                ->whereKey(array_values(array_unique($ids)))
                ->get()
                // Every Capell model has an int or string key; anything else cannot be looked up by id.
                ->filter(static fn (Model $model): bool => is_int($model->getKey()) || is_string($model->getKey()))
                ->keyBy(static function (Model $model): int|string {
                    $key = $model->getKey();

                    return is_int($key) || is_string($key) ? $key : '';
                });
        }

        return $models;
    }

    /**
     * @param  array<string, Collection<int|string, Model>>  $targetModels
     */
    private function targetModel(array $targetModels, mixed $type, mixed $id): ?Model
    {
        if (! is_string($type) || (! is_int($id) && ! is_string($id))) {
            return null;
        }

        $models = $targetModels[$type] ?? null;

        if (! $models instanceof Collection) {
            return null;
        }

        $model = $models->get($id);

        return $model instanceof Model ? $model : null;
    }
}
