<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Models\CampaignLandingPage;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordCampaignConversionAction
{
    use AsAction;

    public function handle(
        CampaignConversionGoal $goal,
        ?Model $visit = null,
        ?Model $event = null,
        ?CampaignLandingPage $landingPage = null,
    ): ?CampaignConversion {
        if (! $goal->is_active) {
            return null;
        }

        $campaignGroup = $goal->campaignGroup;

        $conversion = CampaignConversion::query()->firstOrCreate(
            [
                'campaign_conversion_goal_id' => $goal->getKey(),
                'analytics_visit_id' => $visit?->getKey(),
                'analytics_event_id' => $event?->getKey(),
            ],
            [
                'campaign_group_id' => $campaignGroup->getKey(),
                'campaign_landing_page_id' => $landingPage?->getKey(),
                'site_id' => $event?->getAttribute('site_id') ?? $visit?->getAttribute('site_id') ?? $goal->site_id,
                'language_id' => $event?->getAttribute('language_id') ?? $visit?->getAttribute('language_id'),
                'attribution' => BuildConversionAttributionAction::run($visit, $event),
                'converted_at' => $event?->getAttribute('occurred_at') ?? now()->toImmutable(),
            ],
        );

        return $conversion instanceof CampaignConversion ? $conversion : null;
    }
}
