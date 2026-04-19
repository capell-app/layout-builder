<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

class AwardsBadgesWidget extends AbstractAgencyWidget
{
    public string $name = 'Awards Badges';

    public string $description = 'Grid of awards and recognition badges with year and organizer.';

    public string $view = 'agency::components.awards-badges';

    public string $icon = 'heroicon-o-trophy';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Recognition'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => 'Nice of them to notice.'],
        ['name' => 'awards', 'label' => 'Awards', 'type' => 'repeater', 'default' => [
            ['name' => 'Site of the Day', 'organizer' => 'Awwwards', 'year' => '2025'],
            ['name' => 'Brand System', 'organizer' => 'Brand New', 'year' => '2024'],
            ['name' => 'Gold, Visual Identity', 'organizer' => 'European Design Awards', 'year' => '2024'],
            ['name' => 'Campaign of the Year', 'organizer' => 'The One Show', 'year' => '2023'],
        ]],
    ];
}
