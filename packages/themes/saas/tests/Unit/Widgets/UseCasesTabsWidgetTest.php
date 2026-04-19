<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\UseCasesTabsWidget;

test('use-cases-tabs widget has expected metadata', function () {
    $widget = UseCasesTabsWidget::make();

    expect($widget->name)->toBe('Use Cases Tabs')
        ->and($widget->view)->toBe('saas::components.use-cases-tabs')
        ->and($widget->fieldNames())->toContain('use_cases');
});

test('use-cases-tabs default use cases each include a label and benefits', function () {
    $widget = UseCasesTabsWidget::make();
    $cases = $widget->defaults()['use_cases'];

    foreach ($cases as $case) {
        expect($case['label'] ?? null)->not->toBeNull()
            ->and($case['benefits'] ?? null)->toBeArray();
    }
});
