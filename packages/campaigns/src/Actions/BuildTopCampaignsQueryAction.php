<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Data\Dashboard\CampaignConversionSummaryData;
use Capell\Campaigns\Models\CampaignGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTopCampaignsQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, CampaignConversionSummaryData>
     */
    public function handle(int $limit = 5): Collection
    {
        return CampaignGroup::query()
            ->withCount('conversions')
            ->orderByDesc('conversions_count')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (CampaignGroup $campaignGroup): CampaignConversionSummaryData => new CampaignConversionSummaryData(
                campaignGroupId: (int) $campaignGroup->getKey(),
                campaignName: $campaignGroup->name,
                conversions: (int) $campaignGroup->conversions_count,
                visits: $this->visitCount($campaignGroup),
                conversionRate: $this->conversionRate($campaignGroup),
            ))
            ->values();
    }

    private function visitCount(CampaignGroup $campaignGroup): int
    {
        $visitsTableName = config('capell-analytics.tables.visits', 'analytics_visits');

        if (! is_string($visitsTableName) || ! Schema::hasTable($visitsTableName)) {
            return 0;
        }

        return (int) app('db')
            ->table($visitsTableName)
            ->where('utm_campaign', $campaignGroup->utm_campaign ?? $campaignGroup->slug)
            ->count();
    }

    private function conversionRate(CampaignGroup $campaignGroup): float
    {
        $visits = $this->visitCount($campaignGroup);
        $conversions = (int) $campaignGroup->conversions_count;

        return $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0.0;
    }
}
