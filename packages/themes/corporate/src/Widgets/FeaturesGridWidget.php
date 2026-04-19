<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Widgets;

class FeaturesGridWidget extends AbstractCorporateWidget
{
    public string $name = 'Features Grid';

    public string $description = 'Grid of product/service features with icons.';

    public string $view = 'corporate::components.features-grid';

    public string $icon = 'heroicon-o-squares-2x2';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Why Capell'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => "Everything your marketing site needs, nothing it doesn't."],
        ['name' => 'columns', 'label' => 'Columns', 'type' => 'select', 'default' => '3', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
        ['name' => 'features', 'label' => 'Features', 'type' => 'repeater', 'default' => [
            ['icon' => 'heroicon-o-bolt', 'title' => 'Fast', 'description' => 'Server-side rendered, tuned for speed.'],
            ['icon' => 'heroicon-o-shield-check', 'title' => 'Accessible', 'description' => 'WCAG 2.1 AA compliant out of the box.'],
            ['icon' => 'heroicon-o-cog-6-tooth', 'title' => 'Configurable', 'description' => 'Swap colors, fonts and layouts in minutes.'],
        ]],
    ];
}
