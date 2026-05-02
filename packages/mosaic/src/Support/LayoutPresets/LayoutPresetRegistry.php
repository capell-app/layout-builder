<?php

declare(strict_types=1);

namespace Capell\Mosaic\Support\LayoutPresets;

use Capell\Mosaic\Data\LayoutPresetData;

class LayoutPresetRegistry
{
    /** @var array<string, LayoutPresetData> */
    private array $presets = [];

    public function __construct()
    {
        $this->register(new LayoutPresetData(
            key: 'sidebar-main-footer',
            label: 'Sidebar, main, footer',
            description: 'Sidebar container, primary content area, and full-width footer.',
            containers: ['sidebar', 'main', 'footer'],
            sections: ['hero', 'content', 'signup-footer'],
        ));

        $this->register(new LayoutPresetData(
            key: 'landing',
            label: 'Landing page',
            description: 'Hero, proof, feature grid, and call to action.',
            containers: ['main'],
            sections: ['hero', 'proof', 'features', 'cta'],
        ));
    }

    public function register(LayoutPresetData $preset): void
    {
        $this->presets[$preset->key] = $preset;
    }

    /**
     * @return array<int, LayoutPresetData>
     */
    public function all(): array
    {
        return array_values($this->presets);
    }

    public function bestMatch(string $prompt): LayoutPresetData
    {
        $normalizedPrompt = strtolower($prompt);

        if (str_contains($normalizedPrompt, 'sidebar')) {
            return $this->presets['sidebar-main-footer'];
        }

        return $this->presets['landing'];
    }
}
