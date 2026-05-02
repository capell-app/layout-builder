<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationStyleOption: string implements HasLabel
{
    case Standard = 'standard';
    case Minimal = 'minimal';
    case Prominent = 'prominent';

    public function getLabel(): ?string
    {
        return __('capell-theme-studio-admin::studio.options.navigation.' . $this->value);
    }
}
