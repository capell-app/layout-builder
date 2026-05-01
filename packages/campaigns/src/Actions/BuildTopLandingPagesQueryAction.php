<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Data\Dashboard\CampaignLandingPageSummaryData;
use Capell\Campaigns\Models\CampaignLandingPage;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildTopLandingPagesQueryAction
{
    use AsAction;

    /**
     * @return Collection<int, CampaignLandingPageSummaryData>
     */
    public function handle(int $limit = 5): Collection
    {
        return CampaignLandingPage::query()
            ->with(['campaignGroup'])
            ->withCount('conversions')
            ->orderByDesc('conversions_count')
            ->orderBy('headline')
            ->limit($limit)
            ->get()
            ->map(fn (CampaignLandingPage $landingPage): CampaignLandingPageSummaryData => new CampaignLandingPageSummaryData(
                landingPageId: (int) $landingPage->getKey(),
                landingPageName: $landingPage->headline ?? ('#' . $landingPage->page_id),
                campaignName: $landingPage->campaignGroup?->name ?? '',
                conversions: (int) $landingPage->conversions_count,
            ))
            ->values();
    }
}
