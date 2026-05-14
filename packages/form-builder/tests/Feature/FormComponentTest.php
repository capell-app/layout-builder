<?php

declare(strict_types=1);

use Capell\FormBuilder\Enums\FormFieldType;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Events\FormSubmitted;
use Capell\FormBuilder\Livewire\FormComponent;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
use Illuminate\Support\Facades\Event;

use function Pest\Livewire\livewire;

it('renders and stores a submitted form', function (): void {
    $form = Form::factory()->create([
        'name' => 'Lead form',
        'handle' => 'lead-form',
        'schema' => [
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => FormFieldType::Email->value,
                'required' => true,
            ],
        ],
    ]);

    livewire(FormComponent::class, ['handle' => 'lead-form'])
        ->assertSee('Email')
        ->set('data.email', 'ben@example.com')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::query()->firstOrFail();

    expect($submission->form_id)->toBe($form->getKey())
        ->and($submission->payload->values)->toBe(['email' => 'ben@example.com'])
        ->and($submission->meta->url)->toBeString();
});

it('dispatches submitted payloads when submissions are not stored', function (): void {
    Event::fake([FormSubmitted::class]);

    Form::factory()->create([
        'name' => 'Lead form',
        'handle' => 'lead-form',
        'settings' => [
            'store_submissions' => false,
        ],
        'schema' => [
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => FormFieldType::Email->value,
                'required' => true,
            ],
        ],
    ]);

    livewire(FormComponent::class, ['handle' => 'lead-form'])
        ->set('data.email', 'ben@example.com')
        ->call('submit')
        ->assertSet('submitted', true);

    Event::assertDispatched(
        FormSubmitted::class,
        fn (FormSubmitted $event): bool => $event->payload === ['email' => 'ben@example.com'],
    );

    expect(Submission::query()->count())->toBe(0);
});

it('records honeypot submissions as spam through the Livewire form', function (): void {
    Event::fake([FormSubmitted::class]);

    $form = Form::factory()->create([
        'name' => 'Lead form',
        'handle' => 'lead-form',
        'schema' => [
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => FormFieldType::Email->value,
                'required' => true,
            ],
            [
                'key' => 'company_website',
                'label' => 'Company website',
                'type' => FormFieldType::Honeypot->value,
            ],
        ],
    ]);

    livewire(FormComponent::class, ['handle' => 'lead-form'])
        ->set('data.email', 'bot@example.com')
        ->set('data.company_website', 'https://spam.example')
        ->call('submit')
        ->assertSet('submitted', true);

    $submission = Submission::query()->firstOrFail();

    expect($submission->form_id)->toBe($form->getKey())
        ->and($submission->status)->toBe(SubmissionStatus::Spam)
        ->and($submission->payload->values)->toBe([]);

    Event::assertNotDispatched(FormSubmitted::class);
});

it('records honeypot submissions as spam before validating public fields', function (): void {
    Event::fake([FormSubmitted::class]);

    $form = Form::factory()->create([
        'name' => 'Lead form',
        'handle' => 'lead-form',
        'schema' => [
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => FormFieldType::Email->value,
                'required' => true,
            ],
            [
                'key' => 'company_website',
                'label' => 'Company website',
                'type' => FormFieldType::Honeypot->value,
            ],
        ],
    ]);

    livewire(FormComponent::class, ['handle' => 'lead-form'])
        ->set('data.company_website', 'https://spam.example')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $submission = Submission::query()->firstOrFail();

    expect($submission->form_id)->toBe($form->getKey())
        ->and($submission->status)->toBe(SubmissionStatus::Spam)
        ->and($submission->payload->values)->toBe([]);

    Event::assertNotDispatched(FormSubmitted::class);
});

it('silently swallows honeypot submissions when submissions are not stored', function (): void {
    Event::fake([FormSubmitted::class]);

    Form::factory()->create([
        'name' => 'Lead form',
        'handle' => 'lead-form',
        'settings' => [
            'store_submissions' => false,
        ],
        'schema' => [
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => FormFieldType::Email->value,
                'required' => true,
            ],
            [
                'key' => 'company_website',
                'label' => 'Company website',
                'type' => FormFieldType::Honeypot->value,
            ],
        ],
    ]);

    livewire(FormComponent::class, ['handle' => 'lead-form'])
        ->set('data.email', 'bot@example.com')
        ->set('data.company_website', 'https://spam.example')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    Event::assertNotDispatched(FormSubmitted::class);

    expect(Submission::query()->count())->toBe(0);
});
