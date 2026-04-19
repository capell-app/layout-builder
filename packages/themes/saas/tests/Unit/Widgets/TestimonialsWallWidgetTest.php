<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\TestimonialsWallWidget;

test('testimonials-wall widget has expected metadata', function () {
    $widget = TestimonialsWallWidget::make();

    expect($widget->name)->toBe('Testimonials Wall')
        ->and($widget->view)->toBe('saas::components.testimonials-wall')
        ->and($widget->fieldNames())->toContain('testimonials', 'columns');
});

test('testimonials-wall defaults include quotes with authors', function () {
    $widget = TestimonialsWallWidget::make();

    foreach ($widget->defaults()['testimonials'] as $t) {
        expect($t['quote'] ?? null)->not->toBeNull()
            ->and($t['author'] ?? null)->not->toBeNull();
    }
});
