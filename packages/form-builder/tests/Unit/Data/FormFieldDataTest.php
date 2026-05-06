<?php

declare(strict_types=1);

use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Data\FormSettingsData;
use Capell\FormBuilder\Data\SubmissionPayloadData;
use Capell\FormBuilder\Enums\FormFieldType;

it('creates form field data from editor state', function (): void {
    $field = FormFieldData::from([
        'key' => 'email',
        'label' => 'Email address',
        'type' => 'email',
        'required' => true,
        'placeholder' => 'you@example.com',
        'help_text' => 'Used to reply to your enquiry.',
        'options' => [],
        'default_value' => null,
        'validation_rules' => ['email'],
    ]);

    expect($field->key)->toBe('email')
        ->and($field->type)->toBe(FormFieldType::Email)
        ->and($field->required)->toBeTrue()
        ->and($field->validationRules)->toBe(['email']);
});

it('provides simple default form settings', function (): void {
    $settings = FormSettingsData::from([]);

    expect($settings->successMessage)->toBeNull()
        ->and($settings->storeSubmissions)->toBeTrue()
        ->and($settings->notificationEmail)->toBeNull()
        ->and($settings->collectIpAddress)->toBeTrue()
        ->and($settings->collectUserAgent)->toBeTrue();
});

it('wraps submitted values in payload data', function (): void {
    $payload = SubmissionPayloadData::from([
        'values' => [
            'name' => 'Ben',
            'email' => 'ben@example.com',
        ],
    ]);

    expect($payload->values)->toBe([
        'name' => 'Ben',
        'email' => 'ben@example.com',
    ]);
});
