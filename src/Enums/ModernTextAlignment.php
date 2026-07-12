<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernTextAlignment: string implements HasLabel
{
    case Left = 'left';
    case Center = 'center';
    case Right = 'right';

    public function getLabel(): string
    {
        return match ($this) {
            self::Left => __('capell-layout-builder::widgets.modern.hero_banner.align_left'),
            self::Center => __('capell-layout-builder::widgets.modern.hero_banner.align_center'),
            self::Right => __('capell-layout-builder::widgets.modern.hero_banner.align_right'),
        };
    }
}
