<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Data\Dashboard\BlockGroupData;
use Capell\LayoutBuilder\Data\Dashboard\LayoutHealthData;
use Capell\LayoutBuilder\Data\Dashboard\LeastUsedBlockData;
use Capell\LayoutBuilder\Data\Dashboard\UnusedBlockData;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Widgets\Widget as FilamentWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Override;

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
    #[Override]
    protected function getViewData(): array
    {
        return [
            'data' => $this->getData(),
        ];
    }

    private function getData(): LayoutHealthData
    {
        /** @var class-string<Widget> $blockModel */
        $blockModel = Widget::class;

        // Get total block counts
        $totalBlocks = $blockModel::query()->count();
        $blocksByGroup = $this->getBlocksByGroup($blockModel);

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

        // Get least-used blocks
        $leastUsedBlocks = $this->getLeastUsedBlocks($blockModel);

        // Get unused blocks
        $unusedBlocks = $this->getUnusedBlocks($blockModel);

        return new LayoutHealthData(
            totalBlocks: $totalBlocks,
            totalSections: $totalSections,
            publishedSections: $publishedSections,
            draftSections: $draftSections,
            layoutsWithModifications: $layoutsWithModifications,
            blocksByGroup: $blocksByGroup,
            unusedBlocks: $unusedBlocks,
            leastUsedBlocks: $leastUsedBlocks,
        );
    }

    /**
     * @param  class-string<Widget>  $blockModel
     * @return Collection<int, BlockGroupData>
     */
    private function getBlocksByGroup(string $blockModel): Collection
    {
        $blocks = $blockModel::query()->with('type')->get();
        $groups = [];

        foreach ($blocks as $block) {
            $group = $block->type->group ?? 'default';
            if (! isset($groups[$group])) {
                $groups[$group] = ['total' => 0, 'published' => 0, 'pending' => 0, 'expired' => 0];
            }

            $groups[$group]['total']++;

            $status = $block->publish_status;
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
            $data[] = new BlockGroupData(
                group: $groupName,
                count: $counts['total'],
                published: $counts['published'],
                pending: $counts['pending'],
                expired: $counts['expired'],
            );
        }

        return BlockGroupData::collect($data, Collection::class);
    }

    /**
     * @param  class-string<Widget>  $blockModel
     * @return Collection<int, LeastUsedBlockData>
     */
    private function getLeastUsedBlocks(string $blockModel): Collection
    {
        $leastUsed = $blockModel::query()
            ->with('type')
            ->withCount(['assets' => fn (Builder $query) => $query->distinct('container')])
            ->orderBy('assets_count', 'asc')
            ->limit(5)
            ->get()
            ->map(fn (Widget $block): LeastUsedBlockData => new LeastUsedBlockData(
                name: $block->name ?? $block->class,
                layoutCount: $block->assets_count ?? 0,
                group: $block->type->group ?? 'default',
            ));

        return LeastUsedBlockData::collect($leastUsed, Collection::class);
    }

    /**
     * @param  class-string<Widget>  $blockModel
     * @return Collection<int, UnusedBlockData>
     */
    private function getUnusedBlocks(string $blockModel): Collection
    {
        $unused = $blockModel::query()
            ->with('type')
            ->doesntHave('assets')
            ->get()
            ->map(fn (Widget $block): UnusedBlockData => new UnusedBlockData(
                name: $block->name ?? $block->class,
                group: $block->type->group ?? 'default',
            ));

        return UnusedBlockData::collect($unused, Collection::class);
    }
}
