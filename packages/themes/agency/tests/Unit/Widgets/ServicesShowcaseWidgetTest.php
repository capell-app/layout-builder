<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\ServicesShowcaseWidget;

test('services showcase widget has services field', function () {
    $widget = ServicesShowcaseWidget::make();

    expect($widget->fieldNames())->toContain('services', 'title')
        ->and($widget->view)->toBe('agency::components.services-showcase');
});

test('services showcase default services include expandable detail', function () {
    $widget = ServicesShowcaseWidget::make();
    $defaults = $widget->defaults();

    expect($defaults['services'])->toBeArray()->not->toBeEmpty();
    expect($defaults['services'][0])->toHaveKey('detail');
});
