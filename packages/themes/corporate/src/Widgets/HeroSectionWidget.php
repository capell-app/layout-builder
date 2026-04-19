<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Widgets;

class HeroSectionWidget extends AbstractCorporateWidget
{
    public string $name = 'Hero Section';

    public string $description = 'Full-width hero with heading, subheading and primary CTA.';

    public string $view = 'corporate::components.hero-section';

    public string $icon = 'heroicon-o-sparkles';

    public array $fields = [
        ['name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => 'Capell Corporate'],
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Built for serious businesses.', 'required' => true],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'A trustworthy, accessible, modern site in under an hour.'],
        ['name' => 'cta_label', 'label' => 'CTA Label', 'type' => 'text', 'default' => 'Get started'],
        ['name' => 'cta_url', 'label' => 'CTA URL', 'type' => 'text', 'default' => '#contact'],
        ['name' => 'secondary_cta_label', 'label' => 'Secondary CTA Label', 'type' => 'text', 'default' => 'Learn more'],
        ['name' => 'secondary_cta_url', 'label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '#features'],
        ['name' => 'background_style', 'label' => 'Background', 'type' => 'select', 'default' => 'gradient', 'options' => ['image' => 'Image', 'gradient' => 'Gradient', 'video' => 'Video']],
        ['name' => 'image_url', 'label' => 'Image URL', 'type' => 'text', 'default' => null],
    ];
}
