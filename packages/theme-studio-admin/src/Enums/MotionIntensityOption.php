<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Enums;

use Filament\Support\Contracts\HasLabel;

enum MotionIntensityOption: string implements HasLabel
{
    case None = 'none';
    case Subtle = 'subtle';
    case Expressive = 'expressive';

    public function getLabel(): ?string
    {
        return __('capell-theme-studio-admin::studio.options.motion.' . $this->value);
    }
}
