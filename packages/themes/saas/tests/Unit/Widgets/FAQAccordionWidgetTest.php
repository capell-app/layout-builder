<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\FAQAccordionWidget;

test('faq-accordion widget has expected metadata', function () {
    $widget = FAQAccordionWidget::make();

    expect($widget->name)->toBe('FAQ Accordion')
        ->and($widget->view)->toBe('saas::components.faq-accordion')
        ->and($widget->fieldNames())->toContain('faqs');
});

test('faq-accordion defaults each have question + answer', function () {
    $widget = FAQAccordionWidget::make();

    foreach ($widget->defaults()['faqs'] as $faq) {
        expect($faq['question'] ?? null)->not->toBeNull()
            ->and($faq['answer'] ?? null)->not->toBeNull();
    }
});
