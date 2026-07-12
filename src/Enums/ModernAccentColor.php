<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernAccentColor: string implements HasLabel
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Tertiary = 'tertiary';

    public function getLabel(): string
    {
        return match ($this) {
            self::Primary => __('capell-layout-builder::widgets.common.accent_violet'),
            self::Secondary => __('capell-layout-builder::widgets.common.accent_indigo'),
            self::Tertiary => __('capell-layout-builder::widgets.common.accent_gold'),
        };
    }
}
