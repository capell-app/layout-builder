<?php

declare(strict_types=1);

namespace Capell\Plugins\Database\Factories;

use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketplacePluginLicense>
 */
class MarketplacePluginLicenseFactory extends Factory
{
    protected $model = MarketplacePluginLicense::class;

    public function definition(): array
    {
        return [
            'marketplace_plugin_id' => MarketplacePlugin::factory(),
            'site_id' => $this->faker->uuid(),
            'encrypted_license_key' => 'test_key_' . $this->faker->uuid(),
            'status' => LicenseStatus::Active,
            'seats' => 1,
            'activated_at' => now(),
            'expires_at' => now()->addYear(),
            'last_heartbeat_at' => now(),
            'metadata' => [],
        ];
    }
}
