<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Widgets;

class HeroWithScreenshotWidget extends AbstractSaasWidget
{
    public string $name = 'Hero with Screenshot';

    public string $description = 'Gradient hero with headline, dual CTAs, trust badges and a product screenshot mockup.';

    public string $view = 'saas::components.hero-with-screenshot';

    public string $icon = 'heroicon-o-rocket-launch';

    public array $fields = [
        ['name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => 'Now in public beta'],
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Ship faster. Scale without friction.', 'required' => true],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'The all-in-one platform teams love — built for modern product development.'],
        ['name' => 'primary_cta_label', 'label' => 'Primary CTA Label', 'type' => 'text', 'default' => 'Start free trial'],
        ['name' => 'primary_cta_url', 'label' => 'Primary CTA URL', 'type' => 'text', 'default' => '#signup'],
        ['name' => 'secondary_cta_label', 'label' => 'Secondary CTA Label', 'type' => 'text', 'default' => 'Watch demo'],
        ['name' => 'secondary_cta_url', 'label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '#demo'],
        ['name' => 'screenshot_url', 'label' => 'Product Screenshot URL', 'type' => 'text', 'default' => null],
        ['name' => 'screenshot_alt', 'label' => 'Screenshot Alt Text', 'type' => 'text', 'default' => 'Product screenshot'],
        ['name' => 'trust_badges', 'label' => 'Trust Badges', 'type' => 'repeater', 'default' => [
            'SOC 2 Type II',
            'GDPR Ready',
            '99.99% uptime',
        ]],
    ];
}
