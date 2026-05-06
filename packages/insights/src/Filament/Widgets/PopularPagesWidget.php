<?php

declare(strict_types=1);

namespace Capell\Insights\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Insights\Actions\BuildPopularPagesQueryAction;
use Capell\Insights\Filament\Widgets\Concerns\BuildsInsightsDashboardWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class PopularPagesWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsInsightsDashboardWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'insights_popular_pages';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('insights-popular-pages')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-insights::widgets.popular_pages'))
            ->columns([
                TextColumn::make('path')
                    ->label(__('capell-insights::widgets.path')),
                TextColumn::make('page_views')
                    ->label(__('capell-insights::widgets.page_views'))
                    ->numeric(),
                TextColumn::make('unique_visits')
                    ->label(__('capell-insights::widgets.unique_visits'))
                    ->numeric(),
                TextColumn::make('clicks')
                    ->label(__('capell-insights::widgets.clicks'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array<string, int|string>>
     */
    private function getRecords(): Collection
    {
        return BuildPopularPagesQueryAction::run($this->getInsightsWindow(), 5)
            ->map(fn (array $summary, int $index): array => [
                'id' => 'popular-page-' . $index,
                ...$summary,
            ]);
    }
}
