<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends \Capell\Core\Database\Factories\ThemeFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $state = parent::definition();

        $vendorAssets = $state['meta']['vendor_assets'] ?? [];
        $removeAssets = [
            [
                'path' => 'vendor/capell-frontend',
                'file' => 'resources/css/capell-frontend.css',
            ],
        ];

        $filteredAssets = array_filter(
            $vendorAssets,
            static fn (array $asset): bool => collect($removeAssets)
                ->doesntContain(
                    static fn (array $removeAsset): bool => $asset['path'] === $removeAsset['path']
                        && $asset['file'] === $removeAsset['file'],
                ),
        );

        $state['meta']['vendor_assets'] = array_values($filteredAssets);

        return $state;
    }
}
