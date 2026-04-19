<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

class ProcessFlowWidget extends AbstractAgencyWidget
{
    public string $name = 'Process Flow';

    public string $description = 'Visual, numbered step-by-step process indicator.';

    public string $view = 'agency::components.process-flow';

    public string $icon = 'heroicon-o-arrow-trending-up';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'How we work'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => 'A clear, repeatable process — with room for the weird ideas.'],
        ['name' => 'steps', 'label' => 'Steps', 'type' => 'repeater', 'default' => [
            ['number' => '01', 'title' => 'Discover', 'description' => 'We learn your business, audience and ambitions.'],
            ['number' => '02', 'title' => 'Define', 'description' => 'Strategy, positioning, and the brief that unlocks everything.'],
            ['number' => '03', 'title' => 'Design', 'description' => 'Systems, prototypes and the stuff you put on a billboard.'],
            ['number' => '04', 'title' => 'Deliver', 'description' => 'Production, launch, and measurable results.'],
        ]],
    ];
}
