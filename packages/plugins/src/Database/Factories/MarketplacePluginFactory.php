<?php

declare(strict_types=1);

namespace Capell\Plugins\Database\Factories;

use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Capell\Plugins\Models\MarketplacePlugin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketplacePlugin>
 */
class MarketplacePluginFactory extends Factory
{
    protected $model = MarketplacePlugin::class;

    public function definition(): array
    {
        $name = $this->faker->word();

        return [
            'name' => ucfirst($name),
            'slug' => $name,
            'description' => $this->faker->sentence(),
            'composer_name' => 'vendor/' . $name,
            'vendor' => 'vendor',
            'kind' => PluginKind::Plugin,
            'license_model' => LicenseModel::Perpetual,
            'latest_version' => '1.0.0',
            'homepage_url' => $this->faker->url(),
            'documentation_url' => $this->faker->url(),
            'support_email' => $this->faker->email(),
            'price_monthly' => null,
            'price_yearly' => null,
            'price_once' => null,
            'trial_days' => 0,
            'is_visible' => true,
            'sort_order' => 0,
            'categories' => [],
            'screenshots' => [],
            'compatibility' => [],
            'capabilities' => [],
        ];
    }
}
