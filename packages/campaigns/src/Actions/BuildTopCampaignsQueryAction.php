<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Data\Dashboard\CampaignConversionSummaryData;
use Capell\Campaigns\Models\CampaignGroup;
use Illuminate\Database\ConnectionResolverInterface;
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
            ->map(function (CampaignGroup $campaignGroup): CampaignConversionSummaryData {
                $visits = $this->visitCount($campaignGroup);
                $conversions = $campaignGroup->conversions_count;

                return new CampaignConversionSummaryData(
                    campaignGroupId: (int) $campaignGroup->getKey(),
                    campaignName: $campaignGroup->name,
                    conversions: $conversions,
                    visits: $visits,
                    conversionRate: $this->conversionRate($conversions, $visits),
                );
            })
            ->values();
    }

    private function visitCount(CampaignGroup $campaignGroup): int
    {
        $visitsTableName = config('capell-analytics.tables.visits', 'analytics_visits');

        if (! is_string($visitsTableName) || ! Schema::hasTable($visitsTableName)) {
            return 0;
        }

        return resolve(ConnectionResolverInterface::class)
            ->table($visitsTableName)
            ->where('utm_campaign', $campaignGroup->utm_campaign ?? $campaignGroup->slug)
            ->count();
    }

    private function conversionRate(int $conversions, int $visits): float
    {
        return $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0.0;
    }
}
