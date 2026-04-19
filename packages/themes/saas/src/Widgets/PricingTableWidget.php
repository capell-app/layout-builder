<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Widgets;

class PricingTableWidget extends AbstractSaasWidget
{
    public string $name = 'Pricing Table';

    public string $description = '3-tier pricing cards with annual/monthly toggle and a featured plan.';

    public string $view = 'saas::components.pricing-table';

    public string $icon = 'heroicon-o-currency-dollar';

    public array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Simple, transparent pricing'],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'Pick the plan that fits. Upgrade or downgrade anytime.'],
        ['name' => 'cycle_default', 'label' => 'Default billing cycle', 'type' => 'select', 'default' => 'monthly', 'options' => ['monthly' => 'Monthly', 'annual' => 'Annual']],
        ['name' => 'annual_discount_label', 'label' => 'Annual discount label', 'type' => 'text', 'default' => 'Save 20%'],
        ['name' => 'tiers', 'label' => 'Tiers', 'type' => 'repeater', 'default' => [
            [
                'name' => 'Starter',
                'price_monthly' => 19,
                'price_annual' => 15,
                'description' => 'For individuals getting started.',
                'features' => ['5 projects', 'Community support', 'Basic analytics'],
                'cta_label' => 'Start free',
                'cta_url' => '#signup',
                'highlight' => false,
            ],
            [
                'name' => 'Growth',
                'price_monthly' => 49,
                'price_annual' => 39,
                'description' => 'For growing teams.',
                'features' => ['Unlimited projects', 'Priority support', 'Advanced analytics', 'Integrations'],
                'cta_label' => 'Start free trial',
                'cta_url' => '#signup',
                'highlight' => true,
                'badge' => 'Most popular',
            ],
            [
                'name' => 'Enterprise',
                'price_monthly' => null,
                'price_annual' => null,
                'custom_price_label' => 'Custom',
                'description' => 'For large organizations.',
                'features' => ['SSO + SAML', 'Dedicated success manager', 'Custom SLA', 'Audit logs'],
                'cta_label' => 'Contact sales',
                'cta_url' => '#contact',
                'highlight' => false,
            ],
        ]],
    ];
}
