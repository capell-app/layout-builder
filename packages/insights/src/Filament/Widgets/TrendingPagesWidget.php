<?php

declare(strict_types=1);

namespace Capell\Insights\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Insights\Actions\BuildTrendingPagesQueryAction;
use Capell\Insights\Filament\Widgets\Concerns\BuildsInsightsDashboardWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class TrendingPagesWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsInsightsDashboardWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'insights_trending_pages';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('insights-trending-pages')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-insights::widgets.trending_pages'))
            ->columns([
                TextColumn::make('path')
                    ->label(__('capell-insights::widgets.path')),
                TextColumn::make('current_page_views')
                    ->label(__('capell-insights::widgets.current_page_views'))
                    ->numeric(),
                TextColumn::make('previous_page_views')
                    ->label(__('capell-insights::widgets.previous_page_views'))
                    ->numeric(),
                TextColumn::make('change')
                    ->label(__('capell-insights::widgets.change'))
                    ->numeric(),
                TextColumn::make('change_percentage')
                    ->label(__('capell-insights::widgets.change_percentage'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 1) . '%'),
            ]);
    }

    /**
     * @return Collection<int, array<string, float|int|string>>
     */
    private function getRecords(): Collection
    {
        return BuildTrendingPagesQueryAction::run($this->getInsightsWindow(), 5)
            ->map(fn (array $summary, int $index): array => [
                'id' => 'trending-page-' . $index,
                ...$summary,
            ]);
    }
}
