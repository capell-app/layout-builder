<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Actions;

use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Data\SubmissionMetaData;
use Capell\FormBuilder\Data\SubmissionPayloadData;
use Capell\FormBuilder\Enums\SubmissionStatus;
use Capell\FormBuilder\Events\FormSubmitted;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
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

        event(new FormSubmitted($form, $submission));

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
