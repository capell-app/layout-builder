<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Actions;

use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Enums\FormFieldType;
use Capell\FormBuilder\Models\Form;
use Lorisleiva\Actions\Concerns\AsAction;

class BuildFormValidationRulesAction
{
    use AsAction;

    /**
     * @return array<string, array<int, string>>
     */
    public function handle(Form $form): array
    {
        $rules = [];

        foreach ($form->schema ?? [] as $field) {
            /** @var FormFieldData $field */
            $rules[$field->key] = $this->rulesForField($field);
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    private function rulesForField(FormFieldData $field): array
    {
        if ($field->type === FormFieldType::Honeypot) {
            return ['nullable', 'prohibited'];
        }

        $rules = [$field->required ? 'required' : 'nullable'];

        if (in_array($field->type, [FormFieldType::Text, FormFieldType::Textarea, FormFieldType::Hidden], true)) {
            $rules[] = 'string';
        }

        if ($field->type === FormFieldType::Email) {
            $rules[] = 'email';
        }

        if ($field->type === FormFieldType::Select) {
            $rules[] = 'string';
            $rules[] = 'in:' . implode(',', array_keys($field->options));
        }

        if ($field->type === FormFieldType::Checkbox) {
            $rules[] = 'accepted';
        }

        return array_values(array_unique([
            ...$rules,
            ...$this->allowedEditorRules($field->validationRules),
        ]));
    }

    /**
     * @param  array<int, string>  $rules
     * @return array<int, string>
     */
    private function allowedEditorRules(array $rules): array
    {
        return array_values(array_filter(
            $rules,
            fn (string $rule): bool => preg_match('/^(min|max|size):\d+$/', $rule) === 1
                || in_array($rule, ['email', 'url', 'alpha', 'alpha_dash', 'alpha_num'], true),
        ));
    }
}
