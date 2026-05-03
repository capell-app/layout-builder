<?php

declare(strict_types=1);

namespace Capell\Analytics\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Analytics\Actions\BuildLiveAnalyticsStatsAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class LiveAnalyticsStatsWidget extends BaseWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'analytics_live_stats';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => BuildLiveAnalyticsStatsAction::run(15))
            ->queryStringIdentifier('analytics-live-stats')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-analytics::widgets.live_statistics'))
            ->columns([
                TextColumn::make('metric')
                    ->label(__('capell-analytics::widgets.metric')),
                TextColumn::make('value')
                    ->label(__('capell-analytics::widgets.value')),
            ]);
    }
}
