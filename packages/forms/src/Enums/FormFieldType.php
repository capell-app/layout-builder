<?php

declare(strict_types=1);

namespace Capell\Forms\Enums;

use Filament\Support\Contracts\HasLabel;

enum FormFieldType: string implements HasLabel
{
    case Text = 'text';
    case Email = 'email';
    case Textarea = 'textarea';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case Hidden = 'hidden';
    case Honeypot = 'honeypot';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => __('capell-forms::form.field_type.text'),
            self::Email => __('capell-forms::form.field_type.email'),
            self::Textarea => __('capell-forms::form.field_type.textarea'),
            self::Select => __('capell-forms::form.field_type.select'),
            self::Checkbox => __('capell-forms::form.field_type.checkbox'),
            self::Hidden => __('capell-forms::form.field_type.hidden'),
            self::Honeypot => __('capell-forms::form.field_type.honeypot'),
        };
    }

    public function isStoredInPayload(): bool
    {
        return $this !== self::Honeypot;
    }
}
