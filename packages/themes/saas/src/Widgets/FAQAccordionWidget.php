<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Widgets;

class FAQAccordionWidget extends AbstractSaasWidget
{
    public string $name = 'FAQ Accordion';

    public string $description = 'Expandable FAQ accordion built on native <details>/<summary> for accessibility.';

    public string $view = 'saas::components.faq-accordion';

    public string $icon = 'heroicon-o-question-mark-circle';

    public array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Frequently asked questions'],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => "Can't find what you're looking for? Contact us anytime."],
        ['name' => 'faqs', 'label' => 'FAQs', 'type' => 'repeater', 'default' => [
            ['question' => 'Is there a free trial?', 'answer' => 'Yes — 14 days, no credit card required.'],
            ['question' => 'Can I change plans later?', 'answer' => 'Absolutely. Upgrade or downgrade anytime from the billing page.'],
            ['question' => 'Do you offer annual billing?', 'answer' => 'Yes, annual plans save 20% compared to monthly.'],
            ['question' => 'Is my data secure?', 'answer' => 'All data is encrypted in transit and at rest. We are SOC 2 Type II certified.'],
            ['question' => 'Do you offer SSO?', 'answer' => 'SSO and SAML are available on the Enterprise plan.'],
        ]],
    ];
}
