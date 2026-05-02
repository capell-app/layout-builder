<?php

declare(strict_types=1);

namespace Capell\Analytics\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Analytics\Actions\BuildTrendingPagesQueryAction;
use Capell\Analytics\Data\AnalyticsWindowData;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class TrendingPagesWidget extends BaseWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'analytics_trending_pages';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('analytics-trending-pages')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-analytics::widgets.trending_pages'))
            ->columns([
                TextColumn::make('path')
                    ->label(__('capell-analytics::widgets.path')),
                TextColumn::make('current_page_views')
                    ->label(__('capell-analytics::widgets.current_page_views'))
                    ->numeric(),
                TextColumn::make('previous_page_views')
                    ->label(__('capell-analytics::widgets.previous_page_views'))
                    ->numeric(),
                TextColumn::make('change')
                    ->label(__('capell-analytics::widgets.change'))
                    ->numeric(),
                TextColumn::make('change_percentage')
                    ->label(__('capell-analytics::widgets.change_percentage'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 1) . '%'),
            ]);
    }

    /**
     * @return Collection<int, array<string, float|int|string>>
     */
    private function getRecords(): Collection
    {
        return BuildTrendingPagesQueryAction::run($this->getAnalyticsWindow(), 5)
            ->map(fn (array $summary, int $index): array => [
                'id' => 'trending-page-' . $index,
                ...$summary,
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
