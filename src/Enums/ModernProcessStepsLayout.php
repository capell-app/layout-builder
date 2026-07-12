<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernProcessStepsLayout: string implements HasLabel
{
    case Horizontal = 'horizontal';
    case Vertical = 'vertical';

    public function getLabel(): string
    {
        return match ($this) {
            self::Horizontal => __('capell-layout-builder::widgets.modern.process_steps.layout_horizontal'),
            self::Vertical => __('capell-layout-builder::widgets.modern.process_steps.layout_vertical'),
        };
    }
}
