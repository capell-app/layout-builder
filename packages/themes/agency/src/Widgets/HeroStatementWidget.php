<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

class HeroStatementWidget extends AbstractAgencyWidget
{
    public string $name = 'Hero Statement';

    public string $description = 'Oversized statement hero with asymmetric gradient background and primary CTA.';

    public string $view = 'agency::components.hero-statement';

    public string $icon = 'heroicon-o-megaphone';

    public array $fields = [
        ['name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => 'Creative studio · Est. 2016'],
        ['name' => 'statement', 'label' => 'Statement', 'type' => 'textarea', 'default' => 'Brands worth the attention they demand.', 'required' => true],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'We design identities, products and campaigns for ambitious teams.'],
        ['name' => 'cta_label', 'label' => 'CTA Label', 'type' => 'text', 'default' => 'Start a project'],
        ['name' => 'cta_url', 'label' => 'CTA URL', 'type' => 'text', 'default' => '#inquiry'],
        ['name' => 'secondary_cta_label', 'label' => 'Secondary CTA Label', 'type' => 'text', 'default' => 'See the work'],
        ['name' => 'secondary_cta_url', 'label' => 'Secondary CTA URL', 'type' => 'text', 'default' => '#portfolio'],
        ['name' => 'image_url', 'label' => 'Feature Image URL', 'type' => 'text', 'default' => null],
    ];
}
