<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Models\CampaignGroup;
use Lorisleiva\Actions\Concerns\AsAction;

final class ApplyCampaignPageDefaultsAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handle(array $data, CampaignGroup $campaignGroup): array
    {
        $data['utm_source'] ??= $campaignGroup->utm_source;
        $data['utm_medium'] ??= $campaignGroup->utm_medium;
        $data['utm_campaign'] ??= $campaignGroup->utm_campaign ?? $campaignGroup->slug;

        return $data;
    }
}
