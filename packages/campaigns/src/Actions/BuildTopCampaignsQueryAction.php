<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Data\Dashboard\CampaignConversionSummaryData;
use Capell\Campaigns\Models\CampaignGroup;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTopCampaignsQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, CampaignConversionSummaryData>
     */
    public function handle(int $limit = 5, ?CarbonImmutable $startsAt = null, ?CarbonImmutable $endsAt = null): Collection
    {
        return CampaignGroup::query()
            ->withCount([
                'conversions' => fn (Builder $builder): Builder => $this->applyConversionWindow($builder, $startsAt, $endsAt),
            ])
            ->orderByDesc('conversions_count')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (CampaignGroup $campaignGroup) use ($startsAt, $endsAt): CampaignConversionSummaryData {
                $visits = $this->visitCount($campaignGroup, $startsAt, $endsAt);
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

    private function visitCount(CampaignGroup $campaignGroup, ?CarbonImmutable $startsAt, ?CarbonImmutable $endsAt): int
    {
        $visitsTableName = config('capell-analytics.tables.visits', 'analytics_visits');

        if (! is_string($visitsTableName) || ! Schema::hasTable($visitsTableName)) {
            return 0;
        }

        return resolve(ConnectionResolverInterface::class)
            ->table($visitsTableName)
            ->where('utm_campaign', $campaignGroup->utm_campaign ?? $campaignGroup->slug)
            ->when($startsAt instanceof CarbonImmutable, fn (QueryBuilder $builder): QueryBuilder => $builder->where('last_seen_at', '>=', $startsAt))
            ->when($endsAt instanceof CarbonImmutable, fn (QueryBuilder $builder): QueryBuilder => $builder->where('last_seen_at', '<=', $endsAt))
            ->count();
    }

    private function applyConversionWindow(Builder $builder, ?CarbonImmutable $startsAt, ?CarbonImmutable $endsAt): Builder
    {
        return $builder
            ->when($startsAt instanceof CarbonImmutable, fn (Builder $builder): Builder => $builder->where('converted_at', '>=', $startsAt))
            ->when($endsAt instanceof CarbonImmutable, fn (Builder $builder): Builder => $builder->where('converted_at', '<=', $endsAt));
    }

    private function conversionRate(int $conversions, int $visits): float
    {
        return $visits > 0 ? round(($conversions / $visits) * 100, 2) : 0.0;
    }
}
