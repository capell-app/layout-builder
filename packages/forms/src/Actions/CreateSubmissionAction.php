<?php

declare(strict_types=1);

namespace Capell\Forms\Actions;

use Capell\Forms\Data\FormFieldData;
use Capell\Forms\Data\SubmissionMetaData;
use Capell\Forms\Data\SubmissionPayloadData;
use Capell\Forms\Enums\SubmissionStatus;
use Capell\Forms\Events\FormSubmitted;
use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateSubmissionAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(Form $form, array $input, SubmissionMetaData $meta): Submission
    {
        $validated = Validator::make($input, BuildFormValidationRulesAction::run($form))->validate();

        $submission = new Submission;
        $submission->forceFill([
            'form_id' => $form->getKey(),
            'site_id' => $form->site_id,
            'payload' => new SubmissionPayloadData($this->storedPayload($form, $validated)),
            'meta' => $meta,
            'status' => SubmissionStatus::New,
            'submitted_at' => now(),
        ])->save();

        FormSubmitted::dispatch($form, $submission);

        return $submission;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function storedPayload(Form $form, array $validated): array
    {
        $values = [];

        foreach ($form->schema ?? [] as $field) {
            /** @var FormFieldData $field */
            if (! $field->type->isStoredInPayload()) {
                continue;
            }

            if (array_key_exists($field->key, $validated)) {
                $values[$field->key] = $validated[$field->key];
            }
        }

        return $values;
    }
}
