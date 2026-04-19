<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Widgets;

class ContactFormWidget extends AbstractCorporateWidget
{
    public string $name = 'Contact Form';

    public string $description = 'Accessible contact form with name, email, message and honeypot.';

    public string $view = 'corporate::components.contact-form';

    public string $icon = 'heroicon-o-envelope';

    public array $fields = [
        ['name' => 'title', 'label' => 'Section title', 'type' => 'text', 'default' => 'Contact us'],
        ['name' => 'subtitle', 'label' => 'Section subtitle', 'type' => 'textarea', 'default' => 'Tell us about your project. We\'ll respond within one business day.'],
        ['name' => 'action', 'label' => 'Form action URL', 'type' => 'text', 'default' => '/contact'],
        ['name' => 'submit_label', 'label' => 'Submit label', 'type' => 'text', 'default' => 'Send message'],
        ['name' => 'success_message', 'label' => 'Success message', 'type' => 'text', 'default' => 'Thanks, we\'ll be in touch.'],
    ];
}
