<?php

declare(strict_types=1);

namespace Capell\Search\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Search\Actions\BuildZeroResultSearchesQueryAction;
use Capell\Search\Data\SearchTermSummaryData;
use Capell\Search\Filament\Widgets\Concerns\BuildsSearchInsightsWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class ZeroResultSearchesWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsSearchInsightsWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'zero_result_searches';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('search-zero-result-searches')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-search::dashboard.zero_result_searches'))
            ->columns([
                TextColumn::make('query')
                    ->label(__('capell-search::dashboard.query')),
                TextColumn::make('searches')
                    ->label(__('capell-search::dashboard.searches'))
                    ->numeric(),
                TextColumn::make('resultsCount')
                    ->label(__('capell-search::dashboard.results'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array<string, int|string|float>>
     */
    private function getRecords(): Collection
    {
        return BuildZeroResultSearchesQueryAction::run($this->getInsightsWindow(), 5)
            ->map(fn (SearchTermSummaryData $summary, int $index): array => [
                'id' => 'zero-result-search-' . $index,
                'query' => $summary->query,
                'searches' => $summary->searches,
                'resultsCount' => $summary->resultsCount,
            ]);
    }
}
