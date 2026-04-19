<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Widgets;

class CaseStudiesCarouselWidget extends AbstractCorporateWidget
{
    public string $name = 'Case Studies Carousel';

    public string $description = 'Horizontal carousel of case study cards with outcome metrics.';

    public string $view = 'corporate::components.case-studies-carousel';

    public string $icon = 'heroicon-o-presentation-chart-line';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Case studies'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => 'Real results from real clients.'],
        ['name' => 'studies', 'label' => 'Case studies', 'type' => 'repeater', 'default' => [
            ['client' => 'Acme Corp', 'headline' => '42% faster time to market', 'summary' => 'We replaced their legacy CMS in 6 weeks.', 'url' => '#'],
            ['client' => 'Globex', 'headline' => '3.4x organic traffic', 'summary' => 'Structured data + performance tuning.', 'url' => '#'],
        ]],
    ];
}
