@php
    use Capell\Forms\Enums\FormFieldType;
@endphp

<div class="capell-form">
    @if ($form)
        @if ($submitted)
            <p class="capell-form__message">
                {{ $settings->successMessage ?? __('capell-forms::message.form_submitted') }}
            </p>
        @else
            <form wire:submit="submit" class="capell-form__form">
                @if ($form->description)
                    <p class="capell-form__description">
                        {{ $form->description }}
                    </p>
                @endif

                @foreach ($fields as $field)
                    @php
                        $fieldId = 'capell-form-' . $form->getKey() . '-' . $field->key;
                    @endphp

                    @if ($field->type === FormFieldType::Hidden)
                        <input
                            type="hidden"
                            wire:model="data.{{ $field->key }}"
                            id="{{ $fieldId }}"
                        />
                    @elseif ($field->type === FormFieldType::Honeypot)
                        <input
                            type="text"
                            wire:model="data.{{ $field->key }}"
                            id="{{ $fieldId }}"
                            class="hidden"
                            tabindex="-1"
                            autocomplete="off"
                            aria-hidden="true"
                        />
                    @else
                        <div class="capell-form__field">
                            <label
                                for="{{ $fieldId }}"
                                class="capell-form__label"
                            >
                                {{ $field->label }}
                            </label>

                            @if ($field->type === FormFieldType::Textarea)
                                <textarea
                                    wire:model="data.{{ $field->key }}"
                                    id="{{ $fieldId }}"
                                    class="capell-form__control"
                                    placeholder="{{ $field->placeholder }}"
                                    @required($field->required)
                                ></textarea>
                            @elseif ($field->type === FormFieldType::Select)
                                <select
                                    wire:model="data.{{ $field->key }}"
                                    id="{{ $fieldId }}"
                                    class="capell-form__control"
                                    @required($field->required)
                                >
                                    <option value="">
                                        {{ __('capell-forms::form.select_placeholder') }}
                                    </option>

                                    @foreach ($field->options as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}">
                                            {{ $optionLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif ($field->type === FormFieldType::Checkbox)
                                <input
                                    type="checkbox"
                                    wire:model="data.{{ $field->key }}"
                                    id="{{ $fieldId }}"
                                    class="capell-form__checkbox"
                                />
                            @else
                                <input
                                    type="{{ $field->type->value }}"
                                    wire:model="data.{{ $field->key }}"
                                    id="{{ $fieldId }}"
                                    class="capell-form__control"
                                    placeholder="{{ $field->placeholder }}"
                                    @required($field->required)
                                />
                            @endif

                            @if ($field->helpText)
                                <p class="capell-form__help">
                                    {{ $field->helpText }}
                                </p>
                            @endif

                            @error('data.' . $field->key)
                                <p class="capell-form__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endif
                @endforeach

                <button type="submit" class="capell-form__submit">
                    {{ __('capell-forms::form.submit') }}
                </button>
            </form>
        @endif
    @endif
</div>
