<?php

declare(strict_types=1);

use Capell\Forms\Enums\FormFieldType;
use Capell\Forms\Livewire\FormComponent;
use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;

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
