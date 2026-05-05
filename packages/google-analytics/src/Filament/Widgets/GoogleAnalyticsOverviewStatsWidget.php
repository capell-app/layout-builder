<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\GoogleAnalytics\Actions\BuildGoogleAnalyticsOverviewAction;
use Capell\GoogleAnalytics\Filament\Widgets\Concerns\BuildsGoogleAnalyticsDashboardWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class GoogleAnalyticsOverviewStatsWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsGoogleAnalyticsDashboardWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'google_analytics_overview';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?int $sort = 21;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('google-analytics-overview')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-google-analytics::widgets.overview'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('capell-google-analytics::widgets.metric')),
                TextColumn::make('value')
                    ->label(__('capell-google-analytics::widgets.value')),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, label: string, value: string}>
     */
    private function getRecords(): Collection
    {
        $overview = BuildGoogleAnalyticsOverviewAction::run($this->getGoogleAnalyticsWindow());

        return collect([
            [
                'id' => 'screen-page-views',
                'label' => __('capell-google-analytics::widgets.screen_page_views'),
                'value' => number_format($overview->screenPageViews),
            ],
            [
                'id' => 'sessions',
                'label' => __('capell-google-analytics::widgets.sessions'),
                'value' => number_format($overview->sessions),
            ],
            [
                'id' => 'total-users',
                'label' => __('capell-google-analytics::widgets.total_users'),
                'value' => number_format($overview->totalUsers),
            ],
            [
                'id' => 'engagement-rate',
                'label' => __('capell-google-analytics::widgets.engagement_rate'),
                'value' => number_format($overview->engagementRate * 100, 1) . '%',
            ],
            [
                'id' => 'conversions',
                'label' => __('capell-google-analytics::widgets.conversions'),
                'value' => number_format($overview->conversions),
            ],
        ]);
    }
}
