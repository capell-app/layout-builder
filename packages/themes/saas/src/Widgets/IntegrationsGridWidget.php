<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Widgets;

class IntegrationsGridWidget extends AbstractSaasWidget
{
    public string $name = 'Integrations Grid';

    public string $description = 'Logo grid of third-party integrations with optional descriptions.';

    public string $view = 'saas::components.integrations-grid';

    public string $icon = 'heroicon-o-puzzle-piece';

    public array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Connects with your stack'],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => '100+ integrations and a powerful API.'],
        ['name' => 'columns', 'label' => 'Columns', 'type' => 'select', 'default' => '6', 'options' => ['4' => '4', '5' => '5', '6' => '6']],
        ['name' => 'integrations', 'label' => 'Integrations', 'type' => 'repeater', 'default' => [
            ['name' => 'Slack', 'logo_url' => null, 'url' => '#'],
            ['name' => 'GitHub', 'logo_url' => null, 'url' => '#'],
            ['name' => 'Linear', 'logo_url' => null, 'url' => '#'],
            ['name' => 'Figma', 'logo_url' => null, 'url' => '#'],
            ['name' => 'Notion', 'logo_url' => null, 'url' => '#'],
            ['name' => 'Zapier', 'logo_url' => null, 'url' => '#'],
            ['name' => 'Stripe', 'logo_url' => null, 'url' => '#'],
            ['name' => 'HubSpot', 'logo_url' => null, 'url' => '#'],
            ['name' => 'Segment', 'logo_url' => null, 'url' => '#'],
            ['name' => 'Intercom', 'logo_url' => null, 'url' => '#'],
            ['name' => 'AWS', 'logo_url' => null, 'url' => '#'],
            ['name' => 'Google', 'logo_url' => null, 'url' => '#'],
        ]],
    ];
}
