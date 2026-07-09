<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernStatsLayout: string implements HasLabel
{
    case Horizontal = 'horizontal';
    case Vertical = 'vertical';

    public function getLabel(): string
    {
        return match ($this) {
            self::Horizontal => __('capell-layout-builder::widgets.modern.stats_section.layout_horizontal'),
            self::Vertical => __('capell-layout-builder::widgets.modern.stats_section.layout_vertical'),
        };
    }
}
