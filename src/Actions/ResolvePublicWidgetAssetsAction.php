<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolvePublicWidgetAssetsAction
{
    use AsObject;

    /**
     * @return Collection<int, WidgetAsset>
     */
    public function handle(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): Collection
    {
        return $this->publicAssets(
            $this->attachedAssets($widget, $page, $language, $containerKey, $occurrence),
            $page,
            $language,
        );
    }

    /**
     * @return Collection<int, WidgetAsset>
     */
    public function attachedAssets(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): Collection
    {
        $assets = $widget->relationLoaded('assets')
            ? $widget->assets
            : WidgetAsset::query()
                ->where('widget_id', $widget->id)
                ->where('workspace_id', 0)
                ->where(function (BuilderContract $query) use ($page, $containerKey, $occurrence): void {
                    $query
                        ->where(function (BuilderContract $query) use ($page, $containerKey, $occurrence): void {
                            $query->where([
                                'pageable_type' => $page->getMorphClass(),
                                'pageable_id' => $page->getKey(),
                                'container' => $containerKey,
                                'occurrence' => $occurrence,
                            ]);
                        })
                        ->orWhere(function (BuilderContract $query) use ($occurrence): void {
                            $query
                                ->whereNull('pageable_type')
                                ->whereNull('pageable_id')
                                ->where('occurrence', $occurrence);
                        });
                })
                ->ordered()
                ->get();

        $assets = $assets
            ->filter(fn (WidgetAsset $widgetAsset): bool => $this->belongsToPublicWidgetContext($widgetAsset, $widget, $page, $containerKey, $occurrence))
            ->values();

        if ($assets instanceof EloquentCollection && $assets->isNotEmpty()) {
            $assets->load([
                'asset.translation' => fn (BuilderContract $query): BuilderContract => $this->scopePublicTranslation($query, $language),
            ]);
        }

        return $assets;
    }

    /**
     * @param  Collection<int, WidgetAsset>  $assets
     * @return Collection<int, WidgetAsset>
     */
    public function publicAssets(Collection $assets, Page $page, Language $language): Collection
    {
        return $assets
            ->filter(fn (WidgetAsset $widgetAsset): bool => $this->isPublicAsset($widgetAsset, $page, $language))
            ->values();
    }

    /**
     * @param  Collection<int, WidgetAsset>  $assets
     */
    public function nextVisibilityBoundarySeconds(Collection $assets): ?int
    {
        $now = now();
        $timestamps = $assets
            ->map(fn (WidgetAsset $widgetAsset): ?Model => $widgetAsset->asset)
            ->filter(fn (?Model $asset): bool => $asset instanceof Model && $this->integerAttribute($asset, 'workspace_id') === 0)
            ->flatMap(fn (Model $asset): array => collect([
                $asset->getAttribute('visible_from'),
                $asset->getAttribute('visible_until'),
            ])
                ->filter(fn (mixed $value): bool => $value instanceof DateTimeInterface && $value->getTimestamp() > $now->getTimestamp())
                ->map(fn (DateTimeInterface $value): int => $value->getTimestamp())
                ->all());

        if ($timestamps->isEmpty()) {
            return null;
        }

        $boundary = $timestamps->min();

        return is_int($boundary) ? max(0, $boundary - $now->getTimestamp()) : null;
    }

    private function belongsToPublicWidgetContext(
        WidgetAsset $widgetAsset,
        Widget $widget,
        Page $page,
        string $containerKey,
        int $occurrence,
    ): bool {
        if ($this->integerAttribute($widgetAsset, 'widget_id') !== $this->integerKey($widget)
            || $this->integerAttribute($widgetAsset, 'workspace_id') !== 0
            || $this->integerAttribute($widgetAsset, 'occurrence') !== $occurrence) {
            return false;
        }

        $pageableType = $widgetAsset->getAttribute('pageable_type');
        $pageableId = $widgetAsset->getAttribute('pageable_id');

        if ($pageableType === null && $pageableId === null) {
            return true;
        }

        return $pageableType === $page->getMorphClass()
            && $this->integerValue($pageableId) === $this->integerKey($page)
            && $widgetAsset->getAttribute('container') === $containerKey;
    }

    private function isPublicAsset(WidgetAsset $widgetAsset, Page $page, Language $language): bool
    {
        $asset = $widgetAsset->asset;

        if (! $asset instanceof Model || $this->integerAttribute($asset, 'workspace_id') !== 0) {
            return false;
        }

        if ($asset instanceof Publishable
            && ($asset->trashed() || $asset->isPending() || $asset->isExpired())) {
            return false;
        }

        $assetSiteId = $this->integerAttribute($asset, 'site_id');

        if ($assetSiteId !== null && $assetSiteId !== $this->integerAttribute($page, 'site_id')) {
            return false;
        }

        $translation = $asset->getRelationValue('translation');

        return $translation instanceof Model
            && $this->integerAttribute($translation, 'language_id') === $this->integerKey($language)
            && in_array($this->integerAttribute($translation, 'workspace_id'), [null, 0], true)
            && $translation->getAttribute('deleted_at') === null;
    }

    private function scopePublicTranslation(BuilderContract $query, Language $language): BuilderContract
    {
        $table = $query->getModel()->getTable();

        $query->where('language_id', $language->getKey());

        if (Schema::hasColumn($table, 'workspace_id')) {
            $query->where('workspace_id', 0);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    private function integerAttribute(Model $model, string $attribute): ?int
    {
        return $this->integerValue($model->getAttribute($attribute));
    }

    private function integerKey(Model $model): ?int
    {
        return $this->integerValue($model->getKey());
    }

    private function integerValue(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
