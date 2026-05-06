<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Database\Factories;

use Capell\CampaignStudio\Models\CampaignGroup;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignLandingPage>
 */
class CampaignLandingPageFactory extends Factory
{
    protected $model = CampaignLandingPage::class;

    public function definition(): array
    {
        return [
            'campaign_group_id' => CampaignGroup::factory(),
            'page_id' => 1,
            'headline' => $this->faker->sentence(6),
            'primary_goal_id' => null,
            'utm_content' => 'landing-page',
            'utm_term' => null,
            'is_primary' => false,
        ];
    }
}
