<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Database\Factories;

use Capell\CampaignStudio\Data\ConversionAttributionData;
use Capell\CampaignStudio\Models\CampaignConversion;
use Capell\CampaignStudio\Models\CampaignConversionGoal;
use Capell\CampaignStudio\Models\CampaignGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignConversion>
 */
class CampaignConversionFactory extends Factory
{
    protected $model = CampaignConversion::class;

    public function definition(): array
    {
        return [
            'campaign_group_id' => CampaignGroup::factory(),
            'campaign_landing_page_id' => null,
            'campaign_conversion_goal_id' => CampaignConversionGoal::factory(),
            'insights_visit_id' => null,
            'insights_event_id' => null,
            'site_id' => null,
            'language_id' => null,
            'attribution' => new ConversionAttributionData,
            'converted_at' => now()->toImmutable(),
        ];
    }
}
