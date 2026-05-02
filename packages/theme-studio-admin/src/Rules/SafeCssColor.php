<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeCssColor implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^#[0-9a-fA-F]{6}$/', $value) !== 1) {
            $fail(__('capell-theme-studio-admin::studio.validation.safe_css_color'));
        }
    }
}
