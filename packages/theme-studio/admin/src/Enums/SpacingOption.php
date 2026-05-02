<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Enums;

use Filament\Support\Contracts\HasLabel;

enum SpacingOption: string implements HasLabel
{
    case Compact = 'compact';
    case Balanced = 'balanced';
    case Spacious = 'spacious';

    public function getLabel(): ?string
    {
        return __('capell-theme-studio-admin::studio.options.spacing.' . $this->value);
    }
}
