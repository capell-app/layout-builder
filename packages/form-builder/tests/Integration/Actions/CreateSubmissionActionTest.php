<?php

declare(strict_types=1);

use Capell\FormBuilder\Actions\CreateSubmissionAction;
use Capell\FormBuilder\Data\SubmissionMetaData;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Events\FormSubmitted;
use Capell\FormBuilder\Models\Form;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('validates and stores a submission', function (): void {
    Event::fake([FormSubmitted::class]);

    $form = Form::factory()->create([
        'schema' => [
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
                'validation_rules' => ['email'],
            ],
        ],
    ]);

    $submission = CreateSubmissionAction::run(
        form: $form,
        input: ['email' => 'ben@example.com'],
        meta: new SubmissionMetaData(ipAddress: '127.0.0.1', userAgent: 'Pest'),
    );

    expect($submission->exists)->toBeTrue()
        ->and($submission->form->is($form))->toBeTrue()
        ->and($submission->site_id)->toBe($form->site_id)
        ->and($submission->payload->values)->toBe(['email' => 'ben@example.com'])
        ->and($submission->status)->toBe(SubmissionStatus::New);

    Event::assertDispatched(FormSubmitted::class);
});

it('does not store honeypot values in payload', function (): void {
    $form = Form::factory()->create([
        'schema' => [
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'validation_rules' => ['email']],
            ['key' => 'company_website', 'label' => 'Company website', 'type' => 'honeypot', 'required' => false],
        ],
    ]);

    $submission = CreateSubmissionAction::run(
        form: $form,
        input: ['email' => 'ben@example.com', 'company_website' => null],
        meta: new SubmissionMetaData,
    );

    expect($submission->payload->values)->toBe(['email' => 'ben@example.com']);
});

it('throws a validation exception for invalid data', function (): void {
    $form = Form::factory()->create([
        'schema' => [
            ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'validation_rules' => ['email']],
        ],
    ]);

    CreateSubmissionAction::run(
        form: $form,
        input: ['email' => 'not-an-email'],
        meta: new SubmissionMetaData,
    );
})->throws(ValidationException::class);
