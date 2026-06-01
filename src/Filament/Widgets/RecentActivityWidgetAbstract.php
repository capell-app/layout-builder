<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Data\Dashboard\ActivityItemData;
use Capell\LayoutBuilder\Data\Dashboard\RecentActivityData;
use Filament\Widgets\Widget;
use Override;

final class RecentActivityWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    protected static string $settingsKey = 'recent_activity';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected string $view = 'capell-layout-builder::filament.widgets.recent-activity';

    /** @var int|string|array<string, int|null> */
    protected int|string|array $columnSpan = ['md' => 1];

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function getViewData(): array
    {
        return ['data' => $this->getData()];
    }

    private function getData(): RecentActivityData
    {
        $pageModel = Page::class;

        $pages = $pageModel::query()
            ->with('translation')
            ->latest('updated_at')
            ->limit(10)
            ->get();

        $items = $pages->map(fn (Page $page): ActivityItemData => new ActivityItemData(
            title: $page->title ?? $page->name,
            type: 'page',
            status: $this->resolveStatus($page),
            updatedAt: $page->updated_at,
        ));

        return new RecentActivityData(
            items: $items,
        );
    }

    private function resolveStatus(Page $page): string
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
