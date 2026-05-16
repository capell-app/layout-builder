<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Database\Factories;

use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LayoutPreset>
 */
final class LayoutPresetFactory extends Factory
{
    protected $model = LayoutPreset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'site_id' => Site::factory(),
            'theme_key' => null,
            'name' => $name,
            'key' => str($name)->slug()->toString(),
            'category' => 'general',
            'scope' => 'layout_only',
            'snapshot' => [
                'containers' => [],
                'includeStarterContent' => false,
            ],
        ];
    }
}
