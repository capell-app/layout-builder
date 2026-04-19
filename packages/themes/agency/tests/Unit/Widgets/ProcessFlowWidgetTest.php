<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\ProcessFlowWidget;

test('process flow widget declares a steps field', function () {
    $widget = ProcessFlowWidget::make();

    expect($widget->fieldNames())->toContain('steps')
        ->and($widget->view)->toBe('agency::components.process-flow')
        ->and($widget->name)->toBe('Process Flow');
});

test('process flow steps have numbered defaults', function () {
    $widget = ProcessFlowWidget::make();
    $defaults = $widget->defaults();

    expect($defaults['steps'])->toBeArray()->not->toBeEmpty();
    expect($defaults['steps'][0])->toHaveKey('number');
});
