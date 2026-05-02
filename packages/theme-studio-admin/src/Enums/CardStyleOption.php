<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Enums;

use Filament\Support\Contracts\HasLabel;

enum CardStyleOption: string implements HasLabel
{
    case Subtle = 'subtle';
    case Bordered = 'bordered';
    case Elevated = 'elevated';
    case Layered = 'layered';

    public function getLabel(): ?string
    {
        return __('capell-theme-studio-admin::studio.options.card.' . $this->value);
    }
}
