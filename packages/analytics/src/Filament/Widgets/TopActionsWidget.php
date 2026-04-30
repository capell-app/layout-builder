<?php

declare(strict_types=1);

namespace Capell\Analytics\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Analytics\Actions\BuildTopActionsQueryAction;
use Capell\Analytics\Data\AnalyticsWindowData;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class TopActionsWidget extends BaseWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'analytics_top_actions';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('analytics-top-actions')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-analytics::widgets.top_actions'))
            ->columns([
                TextColumn::make('event_name')
                    ->label(__('capell-analytics::widgets.action')),
                TextColumn::make('events')
                    ->label(__('capell-analytics::widgets.events'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, event_name: string, events: int}>
     */
    private function getRecords(): Collection
    {
        return BuildTopActionsQueryAction::run($this->getAnalyticsWindow(), 5)
            ->map(fn (array $summary, int $index): array => [
                'id' => 'top-action-' . $index,
                'event_name' => $summary['action'],
                'events' => $summary['events'],
            ]);
    }

    private function getAnalyticsWindow(): AnalyticsWindowData
    {
        $endsAt = CarbonImmutable::now();

        return new AnalyticsWindowData(
            startsAt: $endsAt->subDays(30),
            endsAt: $endsAt,
        );
    }
}
