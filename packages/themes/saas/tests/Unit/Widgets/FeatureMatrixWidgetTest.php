<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\FeatureMatrixWidget;

test('feature-matrix widget has expected metadata', function () {
    $widget = FeatureMatrixWidget::make();

    expect($widget->name)->toBe('Feature Matrix')
        ->and($widget->view)->toBe('saas::components.feature-matrix')
        ->and($widget->fieldNames())->toContain('tiers', 'features');
});

test('feature-matrix defaults include at least one tier and feature', function () {
    $widget = FeatureMatrixWidget::make();
    $defaults = $widget->defaults();

    expect($defaults['tiers'])->toBeArray()->not->toBeEmpty()
        ->and($defaults['features'])->toBeArray()->not->toBeEmpty();
});
