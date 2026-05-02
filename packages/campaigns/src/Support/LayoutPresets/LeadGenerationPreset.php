<?php

declare(strict_types=1);

namespace Capell\Campaigns\Support\LayoutPresets;

final class LeadGenerationPreset extends CampaignLayoutPreset
{
    public function key(): string
    {
        return 'campaign-lead-generation';
    }

    public function name(): string
    {
        return 'Campaign - Lead Generation';
    }

    public function containers(): array
    {
        return [
            ['key' => 'hero', 'width' => 'full'],
            ['key' => 'proof', 'width' => 'container'],
            ['key' => 'form', 'width' => 'container'],
        ];
    }

    public function widgets(): array
    {
        return [
            ['container' => 'hero', 'type' => 'campaign-hero'],
            ['container' => 'proof', 'type' => 'campaign-cta-block'],
            ['container' => 'form', 'type' => 'campaign-lead-form'],
        ];
    }
}
