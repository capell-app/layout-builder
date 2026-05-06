<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Support\LayoutPresets;

final class ProductLaunchPreset extends CampaignLayoutPreset
{
    public function key(): string
    {
        return 'campaign-product-launch';
    }

    public function name(): string
    {
        return 'Campaign - Product Launch';
    }

    public function containers(): array
    {
        return [
            ['key' => 'hero', 'width' => 'full'],
            ['key' => 'features', 'width' => 'container'],
            ['key' => 'conversion', 'width' => 'container'],
        ];
    }

    public function widgets(): array
    {
        return [
            ['container' => 'hero', 'type' => 'campaign-hero'],
            ['container' => 'features', 'type' => 'campaign-cta-block'],
            ['container' => 'conversion', 'type' => 'campaign-lead-form'],
        ];
    }
}
