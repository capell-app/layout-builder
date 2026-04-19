<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Widgets;

class CTABannerWidget extends AbstractSaasWidget
{
    public string $name = 'CTA Banner';

    public string $description = 'High-contrast conversion banner with headline, supporting copy and dual CTAs.';

    public string $view = 'saas::components.cta-banner';

    public string $icon = 'heroicon-o-megaphone';

    public array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Ready to ship faster?', 'required' => true],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'Join 10,000+ teams already building with Capell.'],
        ['name' => 'primary_cta_label', 'label' => 'Primary CTA Label', 'type' => 'text', 'default' => 'Start free trial'],
        ['name' => 'primary_cta_url', 'label' => 'Primary CTA URL', 'type' => 'text', 'default' => '#signup'],
        ['name' => 'secondary_cta_label', 'label' => 'Secondary CTA Label', 'type' => 'text', 'default' => 'Talk to sales'],
        ['name' => 'secondary_cta_url', 'label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '#contact'],
        ['name' => 'variant', 'label' => 'Variant', 'type' => 'select', 'default' => 'gradient', 'options' => ['gradient' => 'Gradient', 'solid' => 'Solid', 'inverse' => 'Inverse']],
    ];
}
