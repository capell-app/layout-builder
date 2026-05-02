<?php

declare(strict_types=1);

namespace Capell\Campaigns\Database\Factories;

use Capell\Campaigns\Data\CampaignCtaActionData;
use Capell\Campaigns\Data\UtmData;
use Capell\Campaigns\Models\CampaignCtaBlock;
use Capell\Campaigns\Models\CampaignGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CampaignCtaBlock>
 */
class CampaignCtaBlockFactory extends Factory
{
    protected $model = CampaignCtaBlock::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'campaign_group_id' => CampaignGroup::factory(),
            'site_id' => null,
            'name' => Str::headline($name),
            'key' => Str::slug($name),
            'headline' => $this->faker->sentence(5),
            'body' => $this->faker->paragraph(),
            'actions' => [
                new CampaignCtaActionData(
                    label: 'Get started',
                    url: '/contact',
                    style: 'primary',
                ),
            ],
            'default_utm' => new UtmData(source: 'capell', medium: 'website'),
            'is_active' => true,
        ];
    }
}
