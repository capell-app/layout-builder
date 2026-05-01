<?php

declare(strict_types=1);

namespace Capell\Campaigns\Database\Factories;

use Capell\Campaigns\Data\ConversionAttributionData;
use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Models\CampaignGroup;
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
            'analytics_visit_id' => null,
            'analytics_event_id' => null,
            'site_id' => null,
            'language_id' => null,
            'attribution' => new ConversionAttributionData,
            'converted_at' => now()->toImmutable(),
        ];
    }
}
