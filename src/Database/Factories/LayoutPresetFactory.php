<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Database\Factories;

use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Enums\LayoutPresetMode;
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
        $words = fake()->words(3, true);
        $name = is_string($words) ? $words : implode(' ', $words);

        return [
            'site_id' => Site::factory(),
            'theme_key' => null,
            'name' => $name,
            'key' => str($name)->slug()->toString(),
            'category' => 'general',
            'scope' => 'layout_only',
            'mode' => LayoutPresetMode::Copy,
            'snapshot_version' => 1,
            'revision' => 1,
            'tags' => [],
            'description' => null,
            'snapshot' => [
                'containers' => [],
                'includeStarterContent' => false,
            ],
        ];
    }
}
