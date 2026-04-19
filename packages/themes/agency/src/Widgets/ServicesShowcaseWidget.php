<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

class ServicesShowcaseWidget extends AbstractAgencyWidget
{
    public string $name = 'Services Showcase';

    public string $description = 'Icon-driven services list with expandable detail panels.';

    public string $view = 'agency::components.services-showcase';

    public string $icon = 'heroicon-o-rectangle-stack';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Services'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => 'Full-service, not full of it.'],
        ['name' => 'services', 'label' => 'Services', 'type' => 'repeater', 'default' => [
            ['icon' => 'heroicon-o-paint-brush', 'title' => 'Brand identity', 'summary' => 'Naming, logo systems, type, color and voice.', 'detail' => 'From positioning workshops to complete visual identity systems, built for longevity.'],
            ['icon' => 'heroicon-o-device-phone-mobile', 'title' => 'Digital product', 'summary' => 'Websites, apps and tools people actually use.', 'detail' => 'Design systems, prototypes, build-ready components and a deep bench of engineers.'],
            ['icon' => 'heroicon-o-film', 'title' => 'Campaigns', 'summary' => 'Ideas that earn their place.', 'detail' => 'Print, out-of-home, social, video — whatever the audience is already looking at.'],
            ['icon' => 'heroicon-o-cube-transparent', 'title' => 'Motion & 3D', 'summary' => 'Explainers, product films, brand reels.', 'detail' => 'In-house motion and 3D pipeline from concept to final delivery.'],
        ]],
    ];
}
