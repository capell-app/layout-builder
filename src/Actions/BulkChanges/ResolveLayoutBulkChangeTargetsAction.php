<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\BulkChanges;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\LayoutBulkChangeCriteriaData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static Collection<int, Layout> run(LayoutBulkChangeCriteriaData $criteria)
 */
final class ResolveLayoutBulkChangeTargetsAction
{
    use AsAction;

    /** @return Collection<int, Layout> */
    public function handle(LayoutBulkChangeCriteriaData $criteria): Collection
    {
        return Layout::query()
            ->when($criteria->activeOnly, fn (Builder $query): Builder => $query->where('status', true))
            ->when($criteria->siteIds !== [], fn (Builder $query): Builder => $query->whereIn('site_id', $criteria->siteIds))
            ->when($criteria->themeIds !== [], fn (Builder $query): Builder => $query->whereIn('theme_id', $criteria->themeIds))
            ->when($criteria->groups !== [], fn (Builder $query): Builder => $query->whereIn('group', $criteria->groups))
            ->when($criteria->layoutKeys !== [], fn (Builder $query): Builder => $query->whereIn('key', $criteria->layoutKeys))
            ->when($criteria->requireWidgetKey !== null, fn (Builder $query): Builder => $this->whereContainsWidgetKey($query, (string) $criteria->requireWidgetKey))
            ->ordered()
            ->get();
    }

    /**
     * @param  Builder<Layout>  $query
     * @return Builder<Layout>
     */
    private function whereContainsWidgetKey(Builder $query, string $widgetKey): Builder
    {
        $escapedWidgetKey = addcslashes($widgetKey, '\%_');

        return $query->where(function (Builder $query) use ($escapedWidgetKey): void {
            $query
                ->where('containers', 'like', '%"widget_key":"' . $escapedWidgetKey . '"%')
                ->orWhere('containers', 'like', '%"widgets":["' . $escapedWidgetKey . '"%')
                ->orWhere('containers', 'like', '%,"' . $escapedWidgetKey . '"%');
        });
    }
}
