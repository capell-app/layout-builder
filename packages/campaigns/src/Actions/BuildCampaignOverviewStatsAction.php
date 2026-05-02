<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignGroup;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildCampaignOverviewStatsAction
{
    use AsAction;

    /**
     * @return array{active_campaigns: int, conversions: int, conversion_rate: float}
     */
    public function handle(): array
    {
        $activeCampaigns = CampaignGroup::query()->active()->count();
        $conversions = CampaignConversion::query()->count();
        $visits = $this->campaignVisitCount();

        return [
            'active_campaigns' => $activeCampaigns,
            'conversions' => $conversions,
            'conversion_rate' => $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0.0,
        ];
    }

    private function campaignVisitCount(): int
    {
        $visitsTableName = config('capell-analytics.tables.visits', 'analytics_visits');

        if (! is_string($visitsTableName) || ! Schema::hasTable($visitsTableName)) {
            return 0;
        }

        $groupsTableName = (new CampaignGroup)->getTable();

        return CampaignGroup::query()
            ->join($visitsTableName, $groupsTableName . '.utm_campaign', '=', $visitsTableName . '.utm_campaign')
            ->count($visitsTableName . '.id');
    }
}
