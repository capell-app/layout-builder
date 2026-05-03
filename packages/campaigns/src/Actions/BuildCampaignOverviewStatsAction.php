<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignGroup;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildCampaignOverviewStatsAction
{
    use AsAction;

    /**
     * @return array{active_campaigns: int, conversions: int, conversion_rate: float}
     */
    public function handle(?CarbonImmutable $startsAt = null, ?CarbonImmutable $endsAt = null): array
    {
        $activeCampaigns = CampaignGroup::query()->active()->count();
        $conversions = CampaignConversion::query()
            ->when($startsAt instanceof CarbonImmutable, fn (Builder $builder): Builder => $builder->where('converted_at', '>=', $startsAt))
            ->when($endsAt instanceof CarbonImmutable, fn (Builder $builder): Builder => $builder->where('converted_at', '<=', $endsAt))
            ->count();
        $visits = $this->campaignVisitCount($startsAt, $endsAt);

        return [
            'active_campaigns' => $activeCampaigns,
            'conversions' => $conversions,
            'conversion_rate' => $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0.0,
        ];
    }

    private function campaignVisitCount(?CarbonImmutable $startsAt, ?CarbonImmutable $endsAt): int
    {
        $visitsTableName = config('capell-analytics.tables.visits', 'analytics_visits');

        if (! is_string($visitsTableName) || ! Schema::hasTable($visitsTableName)) {
            return 0;
        }

        $groupsTableName = (new CampaignGroup)->getTable();

        return CampaignGroup::query()
            ->join($visitsTableName, $groupsTableName . '.utm_campaign', '=', $visitsTableName . '.utm_campaign')
            ->when($startsAt instanceof CarbonImmutable, fn (Builder $builder): Builder => $builder->where($visitsTableName . '.last_seen_at', '>=', $startsAt))
            ->when($endsAt instanceof CarbonImmutable, fn (Builder $builder): Builder => $builder->where($visitsTableName . '.last_seen_at', '<=', $endsAt))
            ->count($visitsTableName . '.id');
    }
}
