<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

class PortfolioGridWidget extends AbstractAgencyWidget
{
    public string $name = 'Portfolio Grid';

    public string $description = 'Filterable case-study grid with magazine-style tile layout.';

    public string $view = 'agency::components.portfolio-grid';

    public string $icon = 'heroicon-o-squares-plus';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Selected work'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => 'A decade of brand systems, digital products and campaigns.'],
        ['name' => 'filters', 'label' => 'Category filters', 'type' => 'repeater', 'default' => [
            ['key' => 'all', 'label' => 'All'],
            ['key' => 'branding', 'label' => 'Branding'],
            ['key' => 'digital', 'label' => 'Digital'],
            ['key' => 'campaign', 'label' => 'Campaign'],
        ]],
        ['name' => 'projects', 'label' => 'Projects', 'type' => 'repeater', 'default' => [
            ['client' => 'Northwind', 'title' => 'A type system with teeth', 'category' => 'branding', 'image' => null, 'url' => '#', 'size' => 'large'],
            ['client' => 'Oribe', 'title' => 'Commerce, reimagined', 'category' => 'digital', 'image' => null, 'url' => '#', 'size' => 'medium'],
            ['client' => 'Lumen', 'title' => 'Out-of-home that earns the stare', 'category' => 'campaign', 'image' => null, 'url' => '#', 'size' => 'medium'],
            ['client' => 'Parallel', 'title' => 'Product launch in 30 days', 'category' => 'digital', 'image' => null, 'url' => '#', 'size' => 'small'],
        ]],
    ];
}
