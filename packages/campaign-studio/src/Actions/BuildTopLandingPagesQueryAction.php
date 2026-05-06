<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Data\Dashboard\CampaignLandingPageSummaryData;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTopLandingPagesQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, CampaignLandingPageSummaryData>
     */
    public function handle(int $limit = 5, ?CarbonImmutable $startsAt = null, ?CarbonImmutable $endsAt = null): Collection
    {
        return CampaignLandingPage::query()
            ->with(['campaignGroup'])
            ->withCount([
                'conversions' => fn (Builder $builder): Builder => $builder
                    ->when($startsAt instanceof CarbonImmutable, fn (Builder $builder): Builder => $builder->where('converted_at', '>=', $startsAt))
                    ->when($endsAt instanceof CarbonImmutable, fn (Builder $builder): Builder => $builder->where('converted_at', '<=', $endsAt)),
            ])
            ->orderByDesc('conversions_count')
            ->orderBy('headline')
            ->limit($limit)
            ->get()
            ->map(fn (CampaignLandingPage $landingPage): CampaignLandingPageSummaryData => new CampaignLandingPageSummaryData(
                landingPageId: (int) $landingPage->getKey(),
                landingPageName: $landingPage->headline ?? ('#' . $landingPage->page_id),
                campaignName: $landingPage->campaignGroup?->name ?? '',
                conversions: $landingPage->conversions_count,
            ))
            ->values();
    }
}
