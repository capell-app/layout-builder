<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Enums;

use Filament\Support\Contracts\HasLabel;

enum MediaTreatmentOption: string implements HasLabel
{
    case Natural = 'natural';
    case Framed = 'framed';
    case Immersive = 'immersive';

    public function getLabel(): ?string
    {
        return __('capell-theme-studio-admin::studio.options.media.' . $this->value);
    }
}
