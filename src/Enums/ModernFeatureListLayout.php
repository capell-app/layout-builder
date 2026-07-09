<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernFeatureListLayout: string implements HasLabel
{
    case Vertical = 'vertical';
    case Grid = 'grid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Vertical => __('capell-layout-builder::widgets.modern.feature_list.layout_vertical'),
            self::Grid => __('capell-layout-builder::widgets.modern.feature_list.layout_grid'),
        };
    }
}
