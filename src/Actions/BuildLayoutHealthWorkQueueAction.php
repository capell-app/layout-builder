<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Data\Dashboard\LayoutHealthWorkQueueItemData;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Tables\LayoutsTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Filament\Resources\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static Collection<int, LayoutHealthWorkQueueItemData> run(int $limit = self::DEFAULT_LIMIT)
 */
final class BuildLayoutHealthWorkQueueAction
{
    use AsFake;
    use AsObject;

    private const int DEFAULT_LIMIT = 8;

    private const int MAXIMUM_LIMIT = 12;

    private const int PAGE_REACHABILITY_BATCH_SIZE = 12;

    /**
     * The dashboard work queue is intentionally best-effort. Bound the total
     * policy candidates as well as each query batch, so an inaccessible prefix
     * cannot make the dashboard scan an unbounded number of pages.
     */
    private const int PAGE_REACHABILITY_CANDIDATE_LIMIT = 48;

    /**
     * @return Collection<int, LayoutHealthWorkQueueItemData>
     */
    public function handle(int $limit = self::DEFAULT_LIMIT): Collection
    {
        $actor = auth()->user();
        $limit = min(max($limit, 0), self::MAXIMUM_LIMIT);

        if ($limit === 0 || ! $actor instanceof Authenticatable) {
            return collect();
        }

        /** @var Collection<int, LayoutHealthWorkQueueItemData> $items */
        $items = collect();

        if (WidgetResource::canViewAny()) {
            $items = $items
                ->merge($this->brokenWidgetAssetItems($limit))
                ->merge($this->unscopedWidgetAssetItems($limit))
                ->merge($this->unavailableWidgetItems($limit))
                ->merge($this->unusedWidgetItems($actor, $limit));
        }

        if (LayoutResource::canViewAny()) {
            $items = $items
                ->merge($this->disabledLayoutItems($actor, $limit))
                ->merge($this->unusedLayoutItems($actor, $limit));
        }

        if (PageResource::canViewAny()) {
            $items = $items->merge($this->pageReachabilityItems($limit));
        }

        return $items
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, LayoutHealthWorkQueueItemData>
     */
    private function brokenWidgetAssetItems(int $limit): Collection
    {
        return $this->widgetAssetQuery()
            ->whereDoesntHaveMorph('asset', '*')
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (WidgetAsset $asset): LayoutHealthWorkQueueItemData => new LayoutHealthWorkQueueItemData(
                kind: 'broken_widget_asset',
                title: $this->widgetAssetTitle($asset),
                description: (string) __('capell-layout-builder::widgets.admin.layout_health.work_queue_broken_widget_asset'),
                url: $asset->widget instanceof Widget ? $this->editUrl(WidgetResource::class, $asset->widget) : null,
            ));
    }

    /**
     * @return Collection<int, LayoutHealthWorkQueueItemData>
     */
    private function unscopedWidgetAssetItems(int $limit): Collection
    {
        return $this->widgetAssetQuery()
            ->whereHasMorph('asset', '*')
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('pageable_type')
                ->orWhereNull('pageable_id'))
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (WidgetAsset $asset): LayoutHealthWorkQueueItemData => new LayoutHealthWorkQueueItemData(
                kind: 'unscoped_widget_asset',
                title: $this->widgetAssetTitle($asset),
                description: (string) __('capell-layout-builder::widgets.admin.layout_health.work_queue_unscoped_widget_asset'),
                url: $asset->widget instanceof Widget ? $this->editUrl(WidgetResource::class, $asset->widget) : null,
            ));
    }

    /**
     * @return Collection<int, LayoutHealthWorkQueueItemData>
     */
    private function unavailableWidgetItems(int $limit): Collection
    {
        return Widget::query()
            ->withTrashed()
            ->where('status', false)
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Widget $widget): LayoutHealthWorkQueueItemData => new LayoutHealthWorkQueueItemData(
                kind: 'unavailable_widget',
                title: $widget->name,
                description: (string) __('capell-layout-builder::widgets.admin.layout_health.work_queue_unavailable_widget'),
                url: $this->editUrl(WidgetResource::class, $widget),
            ));
    }

    /**
     * @return Collection<int, LayoutHealthWorkQueueItemData>
     */
    private function unusedWidgetItems(Authenticatable $actor, int $limit): Collection
    {
        $hasAuthoritativeLayoutUsage = SiteScope::isGlobalActor($actor)
            && LayoutResource::canViewAny();
        $query = Widget::query()->withTrashed();
        $model = $query->getModel();
        $countedWidgets = Widget::query()
            ->withTrashed()
            ->select((new Widget)->qualifyColumn('id'))
            ->where('status', true);
        $this->applyWidgetLayoutsCount($countedWidgets);

        return $query
            ->whereIn(
                $model->qualifyColumn($model->getKeyName()),
                function (BaseQueryBuilder $query) use ($countedWidgets): void {
                    $query->fromSub($countedWidgets, 'counted_widgets')
                        ->select('id')
                        ->where('layouts_count', '=', 0);
                },
            )
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Widget $widget): LayoutHealthWorkQueueItemData => new LayoutHealthWorkQueueItemData(
                kind: $hasAuthoritativeLayoutUsage ? 'unused_widget' : 'widget_no_tracked_uses',
                title: $widget->name,
                description: (string) __($hasAuthoritativeLayoutUsage
                    ? 'capell-layout-builder::widgets.admin.layout_health.work_queue_unused_widget'
                    : 'capell-layout-builder::widgets.admin.layout_health.work_queue_widget_no_tracked_uses'),
                url: $this->editUrl(WidgetResource::class, $widget),
            ));
    }

    /**
     * @return Collection<int, LayoutHealthWorkQueueItemData>
     */
    private function disabledLayoutItems(Authenticatable $actor, int $limit): Collection
    {
        return $this->accessibleLayoutQuery($actor)
            ->where('status', false)
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Layout $layout): LayoutHealthWorkQueueItemData => new LayoutHealthWorkQueueItemData(
                kind: 'disabled_layout',
                title: $layout->name,
                description: (string) __('capell-layout-builder::widgets.admin.layout_health.work_queue_disabled_layout'),
                url: $this->editUrl(LayoutResource::class, $layout),
            ));
    }

    /**
     * @return Collection<int, LayoutHealthWorkQueueItemData>
     */
    private function unusedLayoutItems(Authenticatable $actor, int $limit): Collection
    {
        $query = $this->accessibleLayoutQuery($actor);
        $model = $query->getModel();
        $countedLayouts = $this->accessibleLayoutQuery($actor)
            ->where('status', true)
            ->select([
                'layouts.id',
                LayoutsTable::getUsesCountSelect($query, 'pages_count'),
            ]);

        return $query
            ->whereIn(
                $model->qualifyColumn($model->getKeyName()),
                function (BaseQueryBuilder $query) use ($countedLayouts): void {
                    $query->fromSub($countedLayouts, 'counted_layouts')
                        ->select('id')
                        ->where('pages_count', '=', 0);
                },
            )
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Layout $layout): LayoutHealthWorkQueueItemData => new LayoutHealthWorkQueueItemData(
                kind: SiteScope::isGlobalActor($actor) ? 'unused_layout' : 'layout_no_tracked_page_uses',
                title: $layout->name,
                description: (string) __(SiteScope::isGlobalActor($actor)
                    ? 'capell-layout-builder::widgets.admin.layout_health.work_queue_unused_layout'
                    : 'capell-layout-builder::widgets.admin.layout_health.work_queue_layout_no_tracked_page_uses'),
                url: $this->editUrl(LayoutResource::class, $layout),
            ));
    }

    /**
     * @return Collection<int, LayoutHealthWorkQueueItemData>
     */
    private function pageReachabilityItems(int $limit): Collection
    {
        /** @var Collection<int, LayoutHealthWorkQueueItemData> $items */
        $items = collect();
        /** @var array<string, array{can_view: bool, can_edit?: bool}> $accessByBlueprintAndSite */
        $accessByBlueprintAndSite = [];
        $lastPageName = null;
        $lastPageId = null;
        $candidateCount = 0;

        while ($items->count() < $limit && $candidateCount < self::PAGE_REACHABILITY_CANDIDATE_LIMIT) {
            $query = $this->pageReachabilityQuery();

            if (is_string($lastPageName) && (is_int($lastPageId) || is_string($lastPageId))) {
                $this->applyPageReachabilityCursor($query, $lastPageName, $lastPageId);
            }

            /** @var Collection<int, Page> $pages */
            $pages = $query
                ->limit(min(
                    self::PAGE_REACHABILITY_BATCH_SIZE,
                    self::PAGE_REACHABILITY_CANDIDATE_LIMIT - $candidateCount,
                ))
                ->get();

            if ($pages->isEmpty()) {
                break;
            }

            foreach ($pages as $page) {
                $candidateCount++;
                $accessKey = $this->pageAccessCacheKey($page);
                $access = $accessByBlueprintAndSite[$accessKey] ?? null;

                if ($access === null) {
                    $access = ['can_view' => PageResource::canView($page)];
                    $accessByBlueprintAndSite[$accessKey] = $access;
                }

                if (! $access['can_view']) {
                    continue;
                }

                if (! array_key_exists('can_edit', $access)) {
                    $access['can_edit'] = PageResource::canEdit($page);
                    $accessByBlueprintAndSite[$accessKey] = $access;
                }

                $items->push(new LayoutHealthWorkQueueItemData(
                    kind: 'page_uses_disabled_layout',
                    title: $page->name,
                    description: (string) __('capell-layout-builder::widgets.admin.layout_health.work_queue_page_uses_disabled_layout'),
                    url: $this->editUrl(PageResource::class, $page, $access['can_edit']),
                ));

                if ($items->count() === $limit) {
                    break 2;
                }
            }

            $lastPage = $pages->last();
            $lastPageName = $lastPage->name;
            $lastPageId = $lastPage->getKey();
        }

        return $items;
    }

    /**
     * @return Builder<Page>
     */
    private function pageReachabilityQuery(): Builder
    {
        $page = new Page;
        $accessiblePageIds = PageResource::getEloquentQuery()
            ->whereHas('layout', fn (Builder $query): Builder => $query->where('status', false))
            ->select($page->qualifyColumn('id'));

        return Page::query()
            ->whereIn($page->qualifyColumn('id'), $accessiblePageIds)
            ->published()
            ->with([
                'blueprint.roleRestrictions',
                'layout:id,status',
                'site',
            ])
            ->orderBy($page->qualifyColumn('name'))
            ->orderBy($page->qualifyColumn($page->getKeyName()));
    }

    /**
     * @param  Builder<Page>  $query
     */
    private function applyPageReachabilityCursor(Builder $query, string $lastPageName, int|string $lastPageId): void
    {
        $page = $query->getModel();
        $nameColumn = $page->qualifyColumn('name');
        $keyColumn = $page->qualifyColumn($page->getKeyName());

        $query->where(function (Builder $query) use ($keyColumn, $lastPageId, $lastPageName, $nameColumn): void {
            $query->where($nameColumn, '>', $lastPageName)
                ->orWhere(function (Builder $query) use ($keyColumn, $lastPageId, $lastPageName, $nameColumn): void {
                    $query->where($nameColumn, '=', $lastPageName)
                        ->where($keyColumn, '>', $lastPageId);
                });
        });
    }

    private function pageAccessCacheKey(Page $page): string
    {
        return sprintf('%d:%d', $page->blueprint_id, $page->site_id);
    }

    /**
     * @return Builder<WidgetAsset>
     */
    private function widgetAssetQuery(): Builder
    {
        return WidgetAsset::query()
            ->whereHas('widget')
            ->with('widget:id,name');
    }

    /**
     * Mirrors LayoutResource's actor scope while retaining the concrete Layout
     * model type needed by the queue's count projection.
     *
     * @return Builder<Layout>
     */
    private function accessibleLayoutQuery(Authenticatable $actor): Builder
    {
        $query = Layout::query()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);

        if (SiteScope::isGlobalActor($actor)) {
            return $query;
        }

        if (! method_exists($actor, 'getAssignedSiteIds')) {
            return $query->whereRaw('1 = 0');
        }

        $assignedSiteIds = $actor->getAssignedSiteIds();

        return $query->where(function (Builder $query) use ($assignedSiteIds): void {
            $query->whereNull('site_id');

            if ($assignedSiteIds->isNotEmpty()) {
                $query->orWhereIn('site_id', $assignedSiteIds);
            }
        });
    }

    private function widgetAssetTitle(WidgetAsset $asset): string
    {
        $widgetName = $asset->widget instanceof Widget ? $asset->widget->name : null;
        $assetId = $asset->getKey();

        return $widgetName === null || $widgetName === ''
            ? (string) __('capell-layout-builder::widgets.admin.layout_health.work_queue_widget_asset', [
                'id' => is_int($assetId) || is_string($assetId) ? $assetId : '',
            ])
            : (string) __('capell-layout-builder::widgets.admin.layout_health.work_queue_widget_asset_for_widget', ['widget' => $widgetName]);
    }

    /**
     * Apply Widget's protected local scope through Eloquent's public scope API.
     *
     * @param  Builder<Widget>  $query
     */
    private function applyWidgetLayoutsCount(Builder $query): void
    {
        $query->getModel()->callNamedScope('withLayoutsCount', [
            $query,
            LayoutResource::getEloquentQuery(),
        ]);
    }

    /**
     * @param  class-string<resource>  $resource
     */
    private function editUrl(string $resource, Model $record, ?bool $canEdit = null): ?string
    {
        if (($canEdit ?? $resource::canEdit($record)) === false) {
            return null;
        }

        try {
            return $resource::getUrl('edit', ['record' => $record]);
        } catch (Throwable) {
            return null;
        }
    }
}
