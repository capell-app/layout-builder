<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\ContactInquiryWidget;

test('contact inquiry widget has required fields', function () {
    $widget = ContactInquiryWidget::make();

    expect($widget->fieldNames())->toContain('action', 'submit_label', 'budget_options', 'timeline_options')
        ->and($widget->view)->toBe('agency::components.contact-inquiry');
});

test('contact inquiry budget and timeline options are arrays of choices', function () {
    $widget = ContactInquiryWidget::make();
    $defaults = $widget->defaults();

    expect($defaults['budget_options'])->toBeArray()->not->toBeEmpty();
    expect($defaults['timeline_options'])->toBeArray()->not->toBeEmpty();
    expect($defaults['budget_options'][0])->toHaveKeys(['value', 'label']);
});
