<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Enums;

use Filament\Support\Contracts\HasLabel;

enum AlignmentOption: string implements HasLabel
{
    case Left = 'left';
    case Centered = 'centered';
    case Editorial = 'editorial';

    public function getLabel(): ?string
    {
        return __('capell-theme-studio-admin::studio.options.alignment.' . $this->value);
    }
}
