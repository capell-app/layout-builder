<?php

declare(strict_types=1);

namespace Capell\Insights\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Insights\Actions\BuildTopActionsQueryAction;
use Capell\Insights\Filament\Widgets\Concerns\BuildsInsightsDashboardWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class TopActionsWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsInsightsDashboardWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'insights_top_actions';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('insights-top-actions')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-insights::widgets.top_actions'))
            ->columns([
                TextColumn::make('event_name')
                    ->label(__('capell-insights::widgets.action')),
                TextColumn::make('events')
                    ->label(__('capell-insights::widgets.events'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, event_name: string, events: int}>
     */
    private function getRecords(): Collection
    {
        return BuildTopActionsQueryAction::run($this->getInsightsWindow(), 5)
            ->map(fn (array $summary, int $index): array => [
                'id' => 'top-action-' . $index,
                'event_name' => $summary['action'],
                'events' => $summary['events'],
            ]);
    }
}
