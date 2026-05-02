<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Campaigns\Actions\BuildCampaignOverviewStatsAction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CampaignOverviewStatsWidget extends StatsOverviewWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'campaign_overview';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    protected static ?int $sort = 20;

    protected function getStats(): array
    {
        $stats = BuildCampaignOverviewStatsAction::run();

        return [
            Stat::make(__('capell-campaigns::widgets.active_campaigns'), (string) $stats['active_campaigns']),
            Stat::make(__('capell-campaigns::widgets.conversions'), (string) $stats['conversions']),
            Stat::make(__('capell-campaigns::widgets.conversion_rate'), $stats['conversion_rate'] . '%'),
        ];
    }
}
