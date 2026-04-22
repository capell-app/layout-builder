<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Widgets;

use Capell\Admin\Filament\Widgets\CapellWidget;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Mosaic\Data\Dashboard\ActivityItemData;
use Capell\Mosaic\Data\Dashboard\RecentActivityData;

final class RecentActivityWidget extends CapellWidget
{
    protected static string $settingsKey = 'recent_activity';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected string $view = 'capell-mosaic::filament.widgets.recent-activity';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['data' => $this->getData()];
    }

    private function getData(): RecentActivityData
    {
        $pageModel = CapellCore::getModel(ModelEnum::Page);

        $pages = $pageModel::query()
            ->with('translation')
            ->latest('updated_at')
            ->limit(10)
            ->get();

        $items = $pages->map(fn (object $page): ActivityItemData => new ActivityItemData(
            title: $page->title ?? $page->name,
            type: 'page',
            status: $this->resolveStatus($page),
            updatedAt: $page->updated_at,
        ));

        return new RecentActivityData(
            items: $items,
        );
    }

    private function resolveStatus(object $page): string
    {
        if ($page->visible_from === null) {
            return 'draft';
        }

        if ($page->visible_from > now()) {
            return 'scheduled';
        }

        if ($page->visible_until !== null && $page->visible_until <= now()) {
            return 'expired';
        }

        return 'published';
    }
}
