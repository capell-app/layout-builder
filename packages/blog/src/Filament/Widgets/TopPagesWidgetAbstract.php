<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Admin\Filament\Concerns\HasDashboardDateRange;
use Capell\Blog\Data\Dashboard\TopPageData;
use Capell\Blog\Data\Dashboard\TopPagesData;
use Capell\Core\Models\PageView;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TopPagesWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;
    use HasDashboardDateRange;

    protected static string $settingsKey = 'top_pages';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected string $view = 'capell-blog::filament.widgets.top-pages';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return ['data' => $this->getData()];
    }

    private function getData(): TopPagesData
    {
        [$rangeStart, $rangeEnd] = $this->getDashboardDateRange();

        $rows = PageView::query()
            ->select('url', DB::raw('COUNT(*) as views'))
            ->where('viewed_at', '>=', $rangeStart)
            ->where('viewed_at', '<=', $rangeEnd)
            ->groupBy('url')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        $pages = $rows->map(fn (object $row): TopPageData => new TopPageData(
            path: $row->url,
            views: (int) $row->views,
        ));

        return new TopPagesData(
            pages: TopPageData::collect($pages, Collection::class),
        );
    }
}
