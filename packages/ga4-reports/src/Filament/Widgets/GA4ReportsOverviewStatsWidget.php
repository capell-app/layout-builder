<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\GA4Reports\Actions\BuildGA4ReportsOverviewAction;
use Capell\GA4Reports\Filament\Widgets\Concerns\BuildsGA4ReportsDashboardWindow;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

final class GA4ReportsOverviewStatsWidget extends BaseWidget implements CapellWidgetContract
{
    use BuildsGA4ReportsDashboardWindow;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'ga4_reports_overview';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?int $sort = 21;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRecords())
            ->queryStringIdentifier('ga4-reports-overview')
            ->paginated(false)
            ->searchable(false)
            ->heading(__('capell-ga4-reports::widgets.overview'))
            ->columns([
                TextColumn::make('label')
                    ->label(__('capell-ga4-reports::widgets.metric')),
                TextColumn::make('value')
                    ->label(__('capell-ga4-reports::widgets.value')),
            ]);
    }

    /**
     * @return Collection<int, array{id: string, label: string, value: string}>
     */
    private function getRecords(): Collection
    {
        $overview = BuildGA4ReportsOverviewAction::run($this->getGA4ReportsWindow());

        return collect([
            [
                'id' => 'screen-page-views',
                'label' => __('capell-ga4-reports::widgets.screen_page_views'),
                'value' => number_format($overview->screenPageViews),
            ],
            [
                'id' => 'sessions',
                'label' => __('capell-ga4-reports::widgets.sessions'),
                'value' => number_format($overview->sessions),
            ],
            [
                'id' => 'total-users',
                'label' => __('capell-ga4-reports::widgets.total_users'),
                'value' => number_format($overview->totalUsers),
            ],
            [
                'id' => 'engagement-rate',
                'label' => __('capell-ga4-reports::widgets.engagement_rate'),
                'value' => number_format($overview->engagementRate * 100, 1) . '%',
            ],
            [
                'id' => 'conversions',
                'label' => __('capell-ga4-reports::widgets.conversions'),
                'value' => number_format($overview->conversions),
            ],
        ]);
    }
}
