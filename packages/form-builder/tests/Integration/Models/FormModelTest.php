<?php

declare(strict_types=1);

use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Data\FormSettingsData;
use Capell\FormBuilder\Enums\FormFieldType;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
use Illuminate\Database\QueryException;
use Spatie\LaravelData\DataCollection;

it('casts schema and settings to structured data', function (): void {
    $form = Form::factory()->create([
        'schema' => [
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'required' => true,
            ],
        ],
        'settings' => [
            'success_message' => 'Thanks.',
            'store_submissions' => true,
        ],
    ]);

    $form->refresh();

    expect($form->schema)->toBeInstanceOf(DataCollection::class)
        ->and($form->schema)->toHaveCount(1)
        ->and($form->schema->first())->toBeInstanceOf(FormFieldData::class)
        ->and($form->schema->first()->type)->toBe(FormFieldType::Email)
        ->and($form->settings)->toBeInstanceOf(FormSettingsData::class)
        ->and($form->settings->successMessage)->toBe('Thanks.');
});

it('has submissions', function (): void {
    $form = Form::factory()->create();
    $submission = Submission::factory()->for($form)->create();

    expect($form->submissions()->pluck('id')->all())->toBe([$submission->getKey()]);
});

it('scopes active form-builder', function (): void {
    Form::factory()->create(['handle' => 'enabled', 'is_active' => true]);
    Form::factory()->create(['handle' => 'disabled', 'is_active' => false]);

    expect(Form::query()->active()->pluck('handle')->all())->toBe(['enabled']);
});

it('enforces unique handles per site', function (): void {
    $form = Form::factory()->create(['handle' => 'contact']);

    expect(fn (): Form => Form::factory()->create([
        'site_id' => $form->site_id,
        'handle' => 'contact',
    ]))->toThrow(QueryException::class);

    expect(Form::factory()->create(['handle' => 'contact'])->handle)->toBe('contact');
});
