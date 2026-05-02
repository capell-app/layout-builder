<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Enums;

use Filament\Support\Contracts\HasLabel;

enum LayoutPresentationOption: string implements HasLabel
{
    case Structured = 'structured';
    case Editorial = 'editorial';
    case Immersive = 'immersive';

    public function getLabel(): ?string
    {
        return __('capell-theme-studio-admin::studio.options.layout.' . $this->value);
    }
}
