<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Support\LayoutPresets;

final class WebinarPreset extends CampaignLayoutPreset
{
    public function key(): string
    {
        return 'campaign-webinar';
    }

    public function name(): string
    {
        return 'Campaign - Webinar';
    }

    public function containers(): array
    {
        return [
            ['key' => 'hero', 'width' => 'full'],
            ['key' => 'details', 'width' => 'container'],
            ['key' => 'registration', 'width' => 'container'],
        ];
    }

    public function widgets(): array
    {
        return [
            ['container' => 'hero', 'type' => 'campaign-hero'],
            ['container' => 'details', 'type' => 'campaign-cta-block'],
            ['container' => 'registration', 'type' => 'campaign-lead-form'],
        ];
    }
}
