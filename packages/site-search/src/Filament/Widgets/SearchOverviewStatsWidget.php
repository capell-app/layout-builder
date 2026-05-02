<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\SiteSearch\Actions\BuildTopSearchesQueryAction;
use Capell\SiteSearch\Actions\BuildZeroResultSearchesQueryAction;
use Capell\SiteSearch\Filament\Widgets\Concerns\BuildsSearchAnalyticsWindow;
use Filament\Widgets\Widget;

final class SearchOverviewStatsWidget extends Widget implements CapellWidgetContract
{
    use BuildsSearchAnalyticsWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'site_search_overview';

    protected string $view = 'capell-site-search::filament.widgets.search-overview-stats';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?int $sort = 1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $window = $this->getAnalyticsWindow();
        $topSearches = BuildTopSearchesQueryAction::run($window, null);
        $zeroResultSearches = BuildZeroResultSearchesQueryAction::run($window, null);
        $totalSearches = (int) $topSearches->sum('searches');
        $zeroResultTotal = (int) $zeroResultSearches->sum('searches');
        $zeroResultRate = $totalSearches === 0 ? 0.0 : round(($zeroResultTotal / $totalSearches) * 100, 1);

        return [
            'totalSearches' => $totalSearches,
            'uniqueQueries' => $topSearches->count(),
            'totalResults' => (int) $topSearches->sum('resultsCount'),
            'zeroResultRate' => $zeroResultRate,
        ];
    }
}
