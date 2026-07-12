<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum LayoutContainerBorderValue: string implements HasLabel
{
    case None = 'none';
    case Subtle = 'subtle';
    case Strong = 'strong';
    case Top = 'top';
    case Bottom = 'bottom';
    case Vertical = 'vertical';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => __('capell-admin::generic.none'),
            self::Subtle => __('capell-layout-builder::form.border_subtle'),
            self::Strong => __('capell-layout-builder::form.border_strong'),
            self::Top => __('capell-layout-builder::form.border_top'),
            self::Bottom => __('capell-layout-builder::form.border_bottom'),
            self::Vertical => __('capell-layout-builder::form.border_vertical'),
        };
    }
}
