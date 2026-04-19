<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Widgets\FeaturesGridWidget;

test('features grid widget exposes columns and features fields', function () {
    $widget = FeaturesGridWidget::make();

    expect($widget->fieldNames())->toContain('title', 'columns', 'features')
        ->and($widget->view)->toBe('corporate::components.features-grid');
});

test('features grid widget render does not throw for empty data', function () {
    $widget = FeaturesGridWidget::make();

    expect($widget->render([]))->toBeString();
});
