<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Widgets\CaseStudiesCarouselWidget;

test('case studies carousel widget has studies field', function () {
    $widget = CaseStudiesCarouselWidget::make();

    expect($widget->fieldNames())->toContain('studies')
        ->and($widget->view)->toBe('corporate::components.case-studies-carousel');
});
