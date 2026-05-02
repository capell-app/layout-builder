<?php

declare(strict_types=1);

namespace Capell\Forms\Livewire;

use Capell\Forms\Actions\CreateSubmissionAction;
use Capell\Forms\Data\FormFieldData;
use Capell\Forms\Data\FormSettingsData;
use Capell\Forms\Data\SubmissionMetaData;
use Capell\Forms\Enums\FormFieldType;
use Capell\Forms\Events\FormSubmitted;
use Capell\Forms\Models\Form;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Spatie\LaravelData\DataCollection;

final class FormComponent extends Component
{
    public int|string|null $handle = null;

    /** @var array<string, mixed> */
    public array $data = [];

    public ?Form $form = null;

    public bool $submitted = false;

    public function mount(int|string|null $handle = null): void
    {
        $this->handle = $handle;
        $this->form = $this->resolveForm($handle);

        foreach ($this->fields() as $field) {
            $this->data[$field->key] = $field->defaultValue;
        }
    }

    public function submit(): void
    {
        if (! $this->form instanceof Form) {
            return;
        }

        if ($this->hasTriggeredHoneypot()) {
            $this->submitted = true;

            return;
        }

        $this->validate($this->rules());

        $metadata = $this->metadata();
        $settings = $this->settings();
        $submission = null;

        if ($settings->storeSubmissions) {
            $submission = CreateSubmissionAction::run(
                form: $this->form,
                input: $this->data,
                meta: $metadata,
            );
        } else {
            event(new FormSubmitted($this->form, metadata: $metadata));
        }

        $this->submitted = true;
        $this->reset('data');
    }

    public function render(): View
    {
        return view('capell-forms::livewire.form', [
            'fields' => $this->fields(),
            'settings' => $this->settings(),
        ]);
    }

    private function resolveForm(int|string|null $handle): ?Form
    {
        if ($handle === null || $handle === '') {
            return null;
        }

        return Form::query()
            ->where(function (Builder $builder) use ($handle): void {
                if (is_numeric($handle)) {
                    $builder->whereKey((int) $handle);
                }

                $builder->orWhere('handle', (string) $handle);
            })
            ->first();
    }

    /**
     * @return Collection<int, FormFieldData>
     */
    private function fields(): Collection
    {
        $fields = $this->form?->schema;

        if ($fields instanceof DataCollection) {
            return $fields->toCollection();
        }

        return collect();
    }

    private function settings(): FormSettingsData
    {
        return $this->form?->settings instanceof FormSettingsData
            ? $this->form->settings
            : new FormSettingsData(successMessage: __('capell-forms::message.form_submitted'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            if (! $field->type->isStoredInPayload()) {
                continue;
            }

            $fieldRules = $field->validationRules;

            if ($field->required) {
                array_unshift($fieldRules, 'required');
            } else {
                array_unshift($fieldRules, 'nullable');
            }

            if ($field->type === FormFieldType::Email) {
                $fieldRules[] = 'email';
            }

            $rules['data.' . $field->key] = array_values(array_unique($fieldRules));
        }

        return $rules;
    }

    private function hasTriggeredHoneypot(): bool
    {
        foreach ($this->fields() as $field) {
            if ($field->type !== FormFieldType::Honeypot) {
                continue;
            }

            if (filled($this->data[$field->key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function metadata(): SubmissionMetaData
    {
        $request = request();

        return new SubmissionMetaData(
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            url: $request->fullUrl(),
            referer: $request->headers->get('referer'),
        );
    }
}
