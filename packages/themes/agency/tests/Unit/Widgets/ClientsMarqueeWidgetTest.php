<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\ClientsMarqueeWidget;

test('clients marquee widget declares speed and clients fields', function () {
    $widget = ClientsMarqueeWidget::make();

    expect($widget->fieldNames())->toContain('speed', 'clients')
        ->and($widget->view)->toBe('agency::components.clients-marquee');
});

test('clients marquee widget defaults to medium speed', function () {
    $widget = ClientsMarqueeWidget::make();
    $defaults = $widget->defaults();

    expect($defaults['speed'])->toBe('medium');
});
