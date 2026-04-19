<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Widgets\FooterWidget;

test('footer widget has layout and columns fields', function () {
    $widget = FooterWidget::make();

    expect($widget->fieldNames())->toContain('layout', 'columns', 'copyright')
        ->and($widget->view)->toBe('corporate::components.footer');
});
