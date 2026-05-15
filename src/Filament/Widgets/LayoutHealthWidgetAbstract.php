<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\Dashboard\ElementGroupData;
use Capell\LayoutBuilder\Data\Dashboard\LayoutHealthData;
use Capell\LayoutBuilder\Data\Dashboard\LeastUsedElementData;
use Capell\LayoutBuilder\Data\Dashboard\UnusedElementData;
use Capell\LayoutBuilder\Models\Element;
use Filament\Widgets\Widget as FilamentWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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
        /** @var class-string<Element> $elementModel */
        $elementModel = Element::class;

        // Get total element counts
        $totalElements = $elementModel::query()->count();
        $elementsByGroup = $this->getElementsByGroup($elementModel);

        // Get section counts
        $sectionModel = CapellCore::hasAsset('Section')
            ? CapellCore::getAsset('Section')->model
            : null;
        $totalSections = $sectionModel !== null ? $sectionModel::query()->count() : 0;
        $publishedSections = $sectionModel !== null ? $sectionModel::query()->publishedDate()->count() : 0;
        $draftSections = $sectionModel !== null ? $sectionModel::query()->pending()->count() : 0;

        $layoutModel = Layout::class;
        $layoutsWithModifications = Schema::hasColumn('layouts', 'workspace_id')
            ? $layoutModel::query()->where('workspace_id', '>', 0)->count()
            : 0;

        // Get least-used elements
        $leastUsedElements = $this->getLeastUsedElements($elementModel);

        // Get unused elements
        $unusedElements = $this->getUnusedElements($elementModel);

        return new LayoutHealthData(
            totalElements: $totalElements,
            totalSections: $totalSections,
            publishedSections: $publishedSections,
            draftSections: $draftSections,
            layoutsWithModifications: $layoutsWithModifications,
            elementsByGroup: $elementsByGroup,
            unusedElements: $unusedElements,
            leastUsedElements: $leastUsedElements,
        );
    }

    /**
     * @param  class-string<Element>  $elementModel
     * @return Collection<int, ElementGroupData>
     */
    private function getElementsByGroup(string $elementModel): Collection
    {
        $elements = $elementModel::query()->with('type')->get();
        $groups = [];

        foreach ($elements as $element) {
            $group = $element->type?->group ?? 'default';
            if (! isset($groups[$group])) {
                $groups[$group] = ['total' => 0, 'published' => 0, 'pending' => 0, 'expired' => 0];
            }

            $groups[$group]['total']++;

            $status = $element->publish_status;
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
            $data[] = new ElementGroupData(
                group: $groupName,
                count: $counts['total'],
                published: $counts['published'],
                pending: $counts['pending'],
                expired: $counts['expired'],
            );
        }

        return ElementGroupData::collect($data, Collection::class);
    }

    /**
     * @param  class-string<Element>  $elementModel
     * @return Collection<int, LeastUsedElementData>
     */
    private function getLeastUsedElements(string $elementModel): Collection
    {
        $leastUsed = $elementModel::query()
            ->with('type')
            ->withCount(['assets' => fn (Builder $query) => $query->distinct('container')])
            ->orderBy('assets_count', 'asc')
            ->limit(5)
            ->get()
            ->map(fn (Element $element): LeastUsedElementData => new LeastUsedElementData(
                name: $element->name ?? $element->class,
                layoutCount: $element->assets_count ?? 0,
                group: $element->type?->group ?? 'default',
            ));

        return LeastUsedElementData::collect($leastUsed, Collection::class);
    }

    /**
     * @param  class-string<Element>  $elementModel
     * @return Collection<int, UnusedElementData>
     */
    private function getUnusedElements(string $elementModel): Collection
    {
        $unused = $elementModel::query()
            ->with('type')
            ->doesntHave('assets')
            ->get()
            ->map(fn (Element $element): UnusedElementData => new UnusedElementData(
                name: $element->name ?? $element->class,
                group: $element->type?->group ?? 'default',
            ));

        return UnusedElementData::collect($unused, Collection::class);
    }
}
