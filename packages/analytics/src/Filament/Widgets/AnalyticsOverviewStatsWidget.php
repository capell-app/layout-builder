<?php

declare(strict_types=1);

namespace Capell\Analytics\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Analytics\Actions\BuildAnalyticsOverviewStatsAction;
use Capell\Analytics\Filament\Widgets\Concerns\BuildsAnalyticsDashboardWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class AnalyticsOverviewStatsWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsAnalyticsDashboardWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'analytics_overview';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('analytics-overview')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-analytics::widgets.analytics_overview'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('capell-analytics::widgets.metric')),
                TextColumn::make('value')
                    ->label(__('capell-analytics::widgets.value'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, label: string, value: int}>
     */
    private function getRecords(): Collection
    {
        return BuildAnalyticsOverviewStatsAction::run($this->getAnalyticsWindow());
    }
}
