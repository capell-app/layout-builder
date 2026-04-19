<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Widgets;

class UseCasesTabsWidget extends AbstractSaasWidget
{
    public string $name = 'Use Cases Tabs';

    public string $description = 'Tabbed showcase of product use-cases with screenshots and bullet benefits.';

    public string $view = 'saas::components.use-cases-tabs';

    public string $icon = 'heroicon-o-squares-2x2';

    public array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Built for every team'],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'See how teams like yours ship with Capell.'],
        ['name' => 'use_cases', 'label' => 'Use Cases', 'type' => 'repeater', 'default' => [
            [
                'id' => 'engineering',
                'label' => 'Engineering',
                'heading' => 'From commit to production in minutes',
                'description' => 'Automate your release pipeline end-to-end.',
                'benefits' => ['Preview environments', 'Automated tests', 'One-click rollbacks'],
                'image_url' => null,
            ],
            [
                'id' => 'product',
                'label' => 'Product',
                'heading' => 'Ship features faster with confidence',
                'description' => 'Feature flags, experiments and user analytics in one place.',
                'benefits' => ['Feature flags', 'A/B experiments', 'Cohort analytics'],
                'image_url' => null,
            ],
            [
                'id' => 'marketing',
                'label' => 'Marketing',
                'heading' => 'Launch campaigns without waiting on engineering',
                'description' => 'Self-serve content and landing pages for every campaign.',
                'benefits' => ['Landing pages', 'SEO tools', 'Conversion tracking'],
                'image_url' => null,
            ],
        ]],
    ];
}
