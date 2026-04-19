<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\PricingTableWidget;

test('pricing-table widget exposes correct view and fields', function () {
    $widget = PricingTableWidget::make();

    expect($widget->name)->toBe('Pricing Table')
        ->and($widget->view)->toBe('saas::components.pricing-table')
        ->and($widget->fieldNames())->toContain('cycle_default', 'tiers', 'annual_discount_label');
});

test('pricing-table default tiers include a highlighted plan', function () {
    $widget = PricingTableWidget::make();
    $tiers = $widget->defaults()['tiers'];

    $highlighted = array_filter($tiers, static fn ($t) => ! empty($t['highlight']));

    expect($highlighted)->not->toBeEmpty();
});

test('pricing-table default cycle is monthly', function () {
    $widget = PricingTableWidget::make();

    expect($widget->defaults()['cycle_default'])->toBe('monthly');
});
