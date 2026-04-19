<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\AgencyFooterWidget;

test('agency footer widget has wordmark and socials fields', function () {
    $widget = AgencyFooterWidget::make();

    expect($widget->fieldNames())->toContain('wordmark', 'tagline', 'socials', 'copyright')
        ->and($widget->view)->toBe('agency::components.agency-footer');
});

test('agency footer socials default to social channel links', function () {
    $widget = AgencyFooterWidget::make();
    $defaults = $widget->defaults();

    expect($defaults['socials'])->toBeArray()->not->toBeEmpty();
    expect($defaults['socials'][0])->toHaveKeys(['label', 'url']);
});
