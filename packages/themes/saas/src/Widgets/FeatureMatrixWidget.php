<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Widgets;

class FeatureMatrixWidget extends AbstractSaasWidget
{
    public string $name = 'Feature Matrix';

    public string $description = 'Feature comparison matrix with checkmarks across plan tiers.';

    public string $view = 'saas::components.feature-matrix';

    public string $icon = 'heroicon-o-table-cells';

    public array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Everything you need in one platform'],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'Compare features across our plans at a glance.'],
        ['name' => 'tiers', 'label' => 'Tiers', 'type' => 'repeater', 'default' => [
            ['name' => 'Starter', 'highlight' => false],
            ['name' => 'Growth', 'highlight' => true],
            ['name' => 'Enterprise', 'highlight' => false],
        ]],
        ['name' => 'features', 'label' => 'Features', 'type' => 'repeater', 'default' => [
            ['label' => 'Unlimited projects', 'tiers' => [true, true, true]],
            ['label' => 'Priority support', 'tiers' => [false, true, true]],
            ['label' => 'SSO + SAML', 'tiers' => [false, false, true]],
            ['label' => 'Advanced analytics', 'tiers' => [false, true, true]],
            ['label' => 'Custom SLA', 'tiers' => [false, false, true]],
        ]],
    ];
}
