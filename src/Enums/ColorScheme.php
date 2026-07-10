<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ColorScheme: string implements HasLabel
{
    case Auto = 'auto';
    case Light = 'light';
    case Dark = 'dark';

    public function getLabel(): string
    {
        return (string) __('capell-layout-builder::generic.' . $this->value);
    }
}
