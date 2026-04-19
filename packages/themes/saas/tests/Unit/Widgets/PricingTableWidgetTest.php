<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\PricingTableWidget;

test('pricing-table widget exposes correct view and fields', function (): void {
    $widget = PricingTableWidget::make();

    expect($widget->name)->toBe('Pricing Table')
        ->and($widget->view)->toBe('saas::components.pricing-table')
        ->and($widget->fieldNames())->toContain('cycle_default', 'tiers', 'annual_discount_label');
});

test('pricing-table default tiers include a highlighted plan', function (): void {
    $widget = PricingTableWidget::make();
    $tiers = $widget->defaults()['tiers'];

    $highlighted = array_filter($tiers, static fn (array $t): bool => ($t['highlight'] ?? false) !== false);

    expect($highlighted)->not->toBeEmpty();
});

test('pricing-table default cycle is monthly', function (): void {
    $widget = PricingTableWidget::make();

    expect($widget->defaults()['cycle_default'])->toBe('monthly');
});
