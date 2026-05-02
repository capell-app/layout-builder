<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\SiteSearch\Actions\BuildTrendingSearchesQueryAction;
use Capell\SiteSearch\Data\SearchTermSummaryData;
use Capell\SiteSearch\Filament\Widgets\Concerns\BuildsSearchAnalyticsWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class TrendingSearchesWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsSearchAnalyticsWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'trending_searches';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('site-search-trending-searches')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-site-search::dashboard.trending_searches'))
            ->columns([
                TextColumn::make('query')
                    ->label(__('capell-site-search::dashboard.query')),
                TextColumn::make('searches')
                    ->label(__('capell-site-search::dashboard.searches'))
                    ->numeric(),
                TextColumn::make('trendPercentage')
                    ->label(__('capell-site-search::dashboard.trend'))
                    ->formatStateUsing(fn (mixed $state): string => '+' . number_format((float) $state, 1) . '%'),
            ]);
    }

    /**
     * @return Collection<int, array<string, int|string|float>>
     */
    private function getRecords(): Collection
    {
        return BuildTrendingSearchesQueryAction::run($this->getAnalyticsWindow(), 5)
            ->map(fn (SearchTermSummaryData $summary, int $index): array => [
                'id' => 'trending-search-' . $index,
                'query' => $summary->query,
                'searches' => $summary->searches,
                'trendPercentage' => $summary->trendPercentage,
            ]);
    }
}
