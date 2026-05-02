<?php

declare(strict_types=1);

namespace Capell\Campaigns\Database\Factories;

use Capell\Campaigns\Enums\CampaignStatus;
use Capell\Campaigns\Models\CampaignGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CampaignGroup>
 */
class CampaignGroupFactory extends Factory
{
    protected $model = CampaignGroup::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        $slug = Str::slug($name);

        return [
            'site_id' => null,
            'name' => Str::headline($name),
            'slug' => $slug,
            'status' => CampaignStatus::Active,
            'starts_at' => now()->subDay()->toImmutable(),
            'ends_at' => now()->addMonth()->toImmutable(),
            'utm_source' => 'capell',
            'utm_medium' => 'website',
            'utm_campaign' => $slug,
            'budget_amount' => null,
            'notes' => null,
        ];
    }
}
