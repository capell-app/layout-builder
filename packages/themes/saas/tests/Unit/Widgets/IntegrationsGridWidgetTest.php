<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\IntegrationsGridWidget;

test('integrations-grid widget has expected metadata', function () {
    $widget = IntegrationsGridWidget::make();

    expect($widget->name)->toBe('Integrations Grid')
        ->and($widget->view)->toBe('saas::components.integrations-grid')
        ->and($widget->fieldNames())->toContain('integrations', 'columns');
});

test('integrations-grid defaults include multiple integrations', function () {
    $widget = IntegrationsGridWidget::make();

    expect(count($widget->defaults()['integrations']))->toBeGreaterThanOrEqual(6);
});
