<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernCardGridHoverEffect: string implements HasLabel
{
    case Scale = 'scale';
    case Shadow = 'shadow';
    case Lift = 'lift';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scale => __('capell-layout-builder::widgets.modern.card_grid.hover_scale'),
            self::Shadow => __('capell-layout-builder::widgets.modern.card_grid.hover_shadow'),
            self::Lift => __('capell-layout-builder::widgets.modern.card_grid.hover_lift'),
        };
    }
}
