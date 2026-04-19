<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

class ClientsMarqueeWidget extends AbstractAgencyWidget
{
    public string $name = 'Clients Marquee';

    public string $description = 'Animated horizontal marquee of client logos.';

    public string $view = 'agency::components.clients-marquee';

    public string $icon = 'heroicon-o-building-office-2';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Trusted by teams building the new'],
        ['name' => 'speed', 'label' => 'Animation speed', 'type' => 'select', 'default' => 'medium', 'options' => ['slow' => 'Slow', 'medium' => 'Medium', 'fast' => 'Fast']],
        ['name' => 'clients', 'label' => 'Clients', 'type' => 'repeater', 'default' => [
            ['name' => 'Northwind', 'logo' => null],
            ['name' => 'Oribe', 'logo' => null],
            ['name' => 'Lumen', 'logo' => null],
            ['name' => 'Parallel', 'logo' => null],
            ['name' => 'Halcyon', 'logo' => null],
            ['name' => 'Contour', 'logo' => null],
            ['name' => 'Fieldwork', 'logo' => null],
            ['name' => 'Nimbus', 'logo' => null],
        ]],
    ];
}
