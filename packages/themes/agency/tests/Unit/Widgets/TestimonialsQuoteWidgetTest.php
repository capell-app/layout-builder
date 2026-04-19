<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\TestimonialsQuoteWidget;

test('testimonials quote widget has testimonials field', function () {
    $widget = TestimonialsQuoteWidget::make();

    expect($widget->fieldNames())->toContain('testimonials')
        ->and($widget->view)->toBe('agency::components.testimonials-quote');
});

test('testimonials quote defaults include name and role', function () {
    $widget = TestimonialsQuoteWidget::make();
    $defaults = $widget->defaults();

    expect($defaults['testimonials'])->toBeArray()->not->toBeEmpty();
    expect($defaults['testimonials'][0])->toHaveKeys(['quote', 'name', 'role']);
});
