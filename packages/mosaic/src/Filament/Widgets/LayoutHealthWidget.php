<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Widgets;

use Capell\Admin\Filament\Widgets\CapellWidget;
use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Data\Dashboard\LayoutHealthData;
use Capell\Mosaic\Data\Dashboard\LeastUsedWidgetData;
use Capell\Mosaic\Data\Dashboard\UnusedWidgetData;
use Capell\Mosaic\Data\Dashboard\WidgetGroupData;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Models\Widget;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelData\DataCollection;

final class LayoutHealthWidget extends CapellWidget
{
    protected static string $settingsKey = 'layout_health';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['developer'];

    protected string $view = 'capell-mosaic::filament.widgets.layout-health';

    private static ?string $heading = 'Layout health';

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
        $widgetModel = CapellCore::getModel(ModelEnum::Widget);

        // Get total widget counts
        $totalWidgets = $widgetModel::query()->count();
        $widgetsByGroup = $this->getWidgetsByGroup($widgetModel);

        // Get section counts
        $sectionModel = CapellCore::getModel(ModelEnum::Section);
        $totalSections = $sectionModel::query()->count();
        $publishedSections = $sectionModel::query()->where('status', '!=', 'draft')->count();
        $draftSections = $sectionModel::query()->where('status', 'draft')->count();

        // Get layouts with pending modifications
        $layoutModel = CapellCore::getModel(ModelEnum::Layout);
        $layoutsWithModifications = $layoutModel::query()->where('layout_modified', true)->count();

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
     * @return DataCollection<int, WidgetGroupData>
     */
    private function getWidgetsByGroup(string $widgetModel): DataCollection
    {
        $groups = [];

        // Get all widget groups
        $registryWidgets = CapellCore::registry()->widgets();
        foreach ($registryWidgets as $widget) {
            $group = $widget->group ?? 'default';
            if (! isset($groups[$group])) {
                $groups[$group] = [
                    'total' => 0,
                    'published' => 0,
                    'pending' => 0,
                    'expired' => 0,
                ];
            }

            $groups[$group]['total']++;

            // Check status
            $instance = $widgetModel::query()
                ->where('class', $widget->id)
                ->first();

            if ($instance) {
                if ($instance->isPublished()) {
                    $groups[$group]['published']++;
                } elseif ($instance->isPending()) {
                    $groups[$group]['pending']++;
                } elseif ($instance->isExpired()) {
                    $groups[$group]['expired']++;
                }
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

        return DataCollection::from($data);
    }

    /**
     * @param  class-string<Widget>  $widgetModel
     * @return DataCollection<int, LeastUsedWidgetData>
     */
    private function getLeastUsedWidgets(string $widgetModel): DataCollection
    {
        CapellCore::getModel(ModelEnum::WidgetAsset);

        $leastUsed = $widgetModel::query()
            ->withCount(['assets' => fn (Builder $query) => $query->distinct('container')])
            ->orderBy('assets_count', 'asc')
            ->limit(5)
            ->get()
            ->map(fn (Widget $widget): LeastUsedWidgetData => new LeastUsedWidgetData(
                name: $widget->name ?? $widget->class,
                layoutCount: $widget->assets_count ?? 0,
                group: $widget->type?->group ?? 'default',
            ));

        return DataCollection::from($leastUsed);
    }

    /**
     * @param  class-string<Widget>  $widgetModel
     * @return DataCollection<int, UnusedWidgetData>
     */
    private function getUnusedWidgets(string $widgetModel): DataCollection
    {
        $registryWidgets = CapellCore::registry()->widgets();
        $usedWidgetClasses = $widgetModel::query()
            ->pluck('class')
            ->flip()
            ->all();

        $unused = [];
        foreach ($registryWidgets as $widget) {
            if (! isset($usedWidgetClasses[$widget->id])) {
                $unused[] = new UnusedWidgetData(
                    name: $widget->label ?? $widget->id,
                    group: $widget->group ?? 'default',
                );
            }
        }

        return DataCollection::from($unused);
    }
}
