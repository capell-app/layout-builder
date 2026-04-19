<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Widgets\ContactFormWidget;

test('contact form widget has required fields', function () {
    $widget = ContactFormWidget::make();

    expect($widget->fieldNames())->toContain('action', 'submit_label')
        ->and($widget->view)->toBe('corporate::components.contact-form');
});
