<?php

declare(strict_types=1);

namespace Capell\Analytics\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Analytics\Actions\BuildPopularPagesQueryAction;
use Capell\Analytics\Data\AnalyticsWindowData;
use Carbon\CarbonImmutable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class PopularPagesWidget extends BaseWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'analytics_popular_pages';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('analytics-popular-pages')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-analytics::widgets.popular_pages'))
            ->columns([
                TextColumn::make('path')
                    ->label(__('capell-analytics::widgets.path')),
                TextColumn::make('page_views')
                    ->label(__('capell-analytics::widgets.page_views'))
                    ->numeric(),
                TextColumn::make('unique_visits')
                    ->label(__('capell-analytics::widgets.unique_visits'))
                    ->numeric(),
                TextColumn::make('clicks')
                    ->label(__('capell-analytics::widgets.clicks'))
                    ->numeric(),
            ]);
    }

    /**
     * @return Collection<int, array<string, int|string>>
     */
    private function getRecords(): Collection
    {
        return BuildPopularPagesQueryAction::run($this->getAnalyticsWindow(), 5)
            ->map(fn (array $summary, int $index): array => [
                'id' => 'popular-page-' . $index,
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
