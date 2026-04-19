<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\CTABannerWidget;

test('cta-banner widget has expected metadata', function () {
    $widget = CTABannerWidget::make();

    expect($widget->name)->toBe('CTA Banner')
        ->and($widget->view)->toBe('saas::components.cta-banner')
        ->and($widget->fieldNames())->toContain('title', 'primary_cta_label', 'variant');
});

test('cta-banner render produces non-empty html', function () {
    $widget = CTABannerWidget::make();
    $html = $widget->render(['title' => 'Try it now']);

    expect($html)->toBeString()->not->toBeEmpty();
});
