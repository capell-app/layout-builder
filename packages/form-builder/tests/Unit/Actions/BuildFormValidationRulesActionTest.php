<?php

declare(strict_types=1);

use Capell\FormBuilder\Actions\BuildFormValidationRulesAction;
use Capell\FormBuilder\Models\Form;

it('builds validation rules from field data', function (): void {
    $form = Form::factory()->make([
        'schema' => [
            [
                'key' => 'name',
                'label' => 'Name',
                'type' => 'text',
                'required' => true,
                'validation_rules' => ['max:120'],
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
                'validation_rules' => ['email'],
            ],
            [
                'key' => 'company_website',
                'label' => 'Company website',
                'type' => 'honeypot',
                'required' => false,
            ],
        ],
    ]);

    expect(BuildFormValidationRulesAction::run($form))->toBe([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email'],
        'company_website' => ['nullable', 'prohibited'],
    ]);
});

it('ignores unsupported editor validation rules', function (): void {
    $form = Form::factory()->make([
        'schema' => [
            [
                'key' => 'message',
                'label' => 'Message',
                'type' => 'textarea',
                'required' => false,
                'validation_rules' => ['max:500', 'starts_with:<?php'],
            ],
        ],
    ]);

    expect(BuildFormValidationRulesAction::run($form))->toBe([
        'message' => ['nullable', 'string', 'max:500'],
    ]);
});
