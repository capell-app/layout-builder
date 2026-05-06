<?php

declare(strict_types=1);

namespace Capell\Insights\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Insights\Actions\BuildInsightsOverviewStatsAction;
use Capell\Insights\Filament\Widgets\Concerns\BuildsInsightsDashboardWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class InsightsOverviewStatsWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsInsightsDashboardWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'insights_overview';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('insights-overview')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-insights::widgets.insights_overview'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('capell-insights::widgets.metric')),
                TextColumn::make('value')
                    ->label(__('capell-insights::widgets.value'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, label: string, value: int}>
     */
    private function getRecords(): Collection
    {
        return BuildInsightsOverviewStatsAction::run($this->getInsightsWindow());
    }
}
