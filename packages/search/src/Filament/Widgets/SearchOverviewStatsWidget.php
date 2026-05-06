<?php

declare(strict_types=1);

namespace Capell\Search\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Search\Actions\BuildTopSearchesQueryAction;
use Capell\Search\Actions\BuildZeroResultSearchesQueryAction;
use Capell\Search\Filament\Widgets\Concerns\BuildsSearchInsightsWindow;
use Filament\Widgets\Widget;

final class SearchOverviewStatsWidget extends Widget implements CapellWidgetContract
{
    use BuildsSearchInsightsWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'search_overview';

    protected string $view = 'capell-search::filament.widgets.search-overview-stats';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?int $sort = 1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $window = $this->getInsightsWindow();
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
