<?php

declare(strict_types=1);

namespace Capell\Campaigns\Database\Factories;

use Capell\Campaigns\Enums\ConversionGoalType;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Models\CampaignGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CampaignConversionGoal>
 */
class CampaignConversionGoalFactory extends Factory
{
    protected $model = CampaignConversionGoal::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'campaign_group_id' => CampaignGroup::factory(),
            'site_id' => null,
            'name' => Str::headline($name),
            'key' => Str::slug($name),
            'type' => ConversionGoalType::CtaClick,
            'target' => null,
            'value_amount' => null,
            'is_primary' => false,
            'is_active' => true,
        ];
    }
}
