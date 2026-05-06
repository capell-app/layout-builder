<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Data\ConversionAttributionData;
use Capell\CampaignStudio\Models\CampaignConversion;
use Capell\CampaignStudio\Models\CampaignConversionGoal;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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
        ?Model $source = null,
        ?ConversionAttributionData $attribution = null,
    ): ?CampaignConversion {
        if (! $goal->is_active) {
            return null;
        }

        $campaignGroup = $goal->campaignGroup;
        $identity = [
            'campaign_conversion_goal_id' => $goal->getKey(),
            'insights_visit_id' => $visit?->getKey(),
            'insights_event_id' => $event?->getKey(),
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
        ];

        $values = [
            'campaign_group_id' => $campaignGroup->getKey(),
            'campaign_landing_page_id' => $landingPage?->getKey(),
            'site_id' => $event?->getAttribute('site_id') ?? $visit?->getAttribute('site_id') ?? $goal->site_id,
            'language_id' => $event?->getAttribute('language_id') ?? $visit?->getAttribute('language_id'),
            'attribution' => $attribution ?? BuildConversionAttributionAction::run($visit, $event),
            'converted_at' => $this->convertedAt($event),
        ];

        $conversion = $this->hasIdentity($identity)
            ? CampaignConversion::query()->firstOrCreate($identity, $values)
            : CampaignConversion::query()->create([...$identity, ...$values]);

        return $conversion instanceof CampaignConversion ? $conversion : null;
    }

    /**
     * @param  array<string, mixed>  $identity
     */
    private function hasIdentity(array $identity): bool
    {
        return $identity['insights_visit_id'] !== null
            || $identity['insights_event_id'] !== null
            || $identity['source_type'] !== null
            || $identity['source_id'] !== null;
    }

    private function convertedAt(?Model $event): CarbonImmutable
    {
        $occurredAt = $event?->getAttribute('occurred_at');

        if ($occurredAt instanceof CarbonInterface) {
            return $occurredAt->toImmutable();
        }

        return now()->toImmutable();
    }
}
