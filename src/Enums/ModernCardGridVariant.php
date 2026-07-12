<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernCardGridVariant: string implements HasLabel
{
    case Default = 'default';
    case Elevated = 'elevated';
    case Glass = 'glass';

    public function getLabel(): string
    {
        return match ($this) {
            self::Default => __('capell-layout-builder::widgets.modern.card_grid.variant_default'),
            self::Elevated => __('capell-layout-builder::widgets.modern.card_grid.variant_elevated'),
            self::Glass => __('capell-layout-builder::widgets.modern.card_grid.variant_glass'),
        };
    }
}
