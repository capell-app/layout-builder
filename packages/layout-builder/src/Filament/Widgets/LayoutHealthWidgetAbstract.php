<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\Dashboard\LayoutHealthData;
use Capell\LayoutBuilder\Data\Dashboard\LeastUsedWidgetData;
use Capell\LayoutBuilder\Data\Dashboard\UnusedWidgetData;
use Capell\LayoutBuilder\Data\Dashboard\WidgetGroupData;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Widgets\Widget as FilamentWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class LayoutHealthWidgetAbstract extends FilamentWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    protected static string $settingsKey = 'layout_health';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['super_admin'];

    protected string $view = 'capell-layout-builder::filament.widgets.layout-health';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'data' => $this->getData(),
        ];
    }

    private function getData(): LayoutHealthData
    {
        /** @var class-string<Widget> $widgetModel */
        $widgetModel = Widget::class;

        // Get total widget counts
        $totalWidgets = $widgetModel::query()->count();
        $widgetsByGroup = $this->getWidgetsByGroup($widgetModel);

        // Get section counts
        $sectionModel = CapellCore::hasAsset('Section')
            ? CapellCore::getAsset('Section')->model
            : null;
        $totalSections = $sectionModel !== null ? $sectionModel::query()->count() : 0;
        $publishedSections = $sectionModel !== null ? $sectionModel::query()->publishedDate()->count() : 0;
        $draftSections = $sectionModel !== null ? $sectionModel::query()->pending()->count() : 0;

        // Layouts with workspace_id > 0 are draft copies with pending modifications
        $layoutModel = Layout::class;
        $layoutsWithModifications = $layoutModel::query()->where('workspace_id', '>', 0)->count();

        // Get least-used widgets
        $leastUsedWidgets = $this->getLeastUsedWidgets($widgetModel);

        // Get unused widgets
        $unusedWidgets = $this->getUnusedWidgets($widgetModel);

        return new LayoutHealthData(
            totalWidgets: $totalWidgets,
            totalSections: $totalSections,
            publishedSections: $publishedSections,
            draftSections: $draftSections,
            layoutsWithModifications: $layoutsWithModifications,
            widgetsByGroup: $widgetsByGroup,
            unusedWidgets: $unusedWidgets,
            leastUsedWidgets: $leastUsedWidgets,
        );
    }

    /**
     * @param  class-string<Widget>  $widgetModel
     * @return Collection<int, WidgetGroupData>
     */
    private function getWidgetsByGroup(string $widgetModel): Collection
    {
        $widgets = $widgetModel::query()->with('type')->get();
        $groups = [];

        foreach ($widgets as $widget) {
            $group = $widget->type?->group ?? 'default';
            if (! isset($groups[$group])) {
                $groups[$group] = ['total' => 0, 'published' => 0, 'pending' => 0, 'expired' => 0];
            }

            $groups[$group]['total']++;

            $status = $widget->publish_status;
            if ($status === PublishStatusEnum::published) {
                $groups[$group]['published']++;
            } elseif ($status === PublishStatusEnum::pending) {
                $groups[$group]['pending']++;
            } elseif ($status === PublishStatusEnum::expired) {
                $groups[$group]['expired']++;
            }
        }

        $data = [];
        foreach ($groups as $groupName => $counts) {
            $data[] = new WidgetGroupData(
                group: $groupName,
                count: $counts['total'],
                published: $counts['published'],
                pending: $counts['pending'],
                expired: $counts['expired'],
            );
        }

        return WidgetGroupData::collect($data, Collection::class);
    }

    /**
     * @param  class-string<Widget>  $widgetModel
     * @return Collection<int, LeastUsedWidgetData>
     */
    private function getLeastUsedWidgets(string $widgetModel): Collection
    {
        $leastUsed = $widgetModel::query()
            ->with('type')
            ->withCount(['assets' => fn (Builder $query) => $query->distinct('container')])
            ->orderBy('assets_count', 'asc')
            ->limit(5)
            ->get()
            ->map(fn (Widget $widget): LeastUsedWidgetData => new LeastUsedWidgetData(
                name: $widget->name ?? $widget->class,
                layoutCount: $widget->assets_count ?? 0,
                group: $widget->type?->group ?? 'default',
            ));

        return LeastUsedWidgetData::collect($leastUsed, Collection::class);
    }

    /**
     * @param  class-string<Widget>  $widgetModel
     * @return Collection<int, UnusedWidgetData>
     */
    private function getUnusedWidgets(string $widgetModel): Collection
    {
        $unused = $widgetModel::query()
            ->with('type')
            ->doesntHave('assets')
            ->get()
            ->map(fn (Widget $widget): UnusedWidgetData => new UnusedWidgetData(
                name: $widget->name ?? $widget->class,
                group: $widget->type?->group ?? 'default',
            ));

        return UnusedWidgetData::collect($unused, Collection::class);
    }
}
