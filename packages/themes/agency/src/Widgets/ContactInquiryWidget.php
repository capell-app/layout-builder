<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Widgets;

class ContactInquiryWidget extends AbstractAgencyWidget
{
    public string $name = 'Contact Inquiry';

    public string $description = 'Project inquiry form with name, email, message plus budget and timeline fields.';

    public string $view = 'agency::components.contact-inquiry';

    public string $icon = 'heroicon-o-paper-airplane';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Start a project'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => 'Tell us what you\'re making. We reply within one business day.'],
        ['name' => 'action', 'label' => 'Form action URL', 'type' => 'text', 'default' => '/inquiry'],
        ['name' => 'submit_label', 'label' => 'Submit label', 'type' => 'text', 'default' => 'Send inquiry'],
        ['name' => 'success_message', 'label' => 'Success message', 'type' => 'text', 'default' => 'Thanks. We\'ll be in touch shortly.'],
        ['name' => 'budget_options', 'label' => 'Budget options', 'type' => 'repeater', 'default' => [
            ['value' => 'under-25k', 'label' => 'Under $25k'],
            ['value' => '25k-75k', 'label' => '$25k – $75k'],
            ['value' => '75k-200k', 'label' => '$75k – $200k'],
            ['value' => '200k-plus', 'label' => '$200k+'],
        ]],
        ['name' => 'timeline_options', 'label' => 'Timeline options', 'type' => 'repeater', 'default' => [
            ['value' => 'urgent', 'label' => 'ASAP (under 4 weeks)'],
            ['value' => 'quarter', 'label' => 'This quarter'],
            ['value' => 'half', 'label' => 'Next 6 months'],
            ['value' => 'flexible', 'label' => 'Flexible'],
        ]],
    ];
}
