<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernTestimonialsColumnCount: string implements HasLabel
{
    case One = '1';
    case Two = '2';
    case Three = '3';

    public function getLabel(): string
    {
        return match ($this) {
            self::One => __('capell-layout-builder::widgets.modern.testimonials.columns_1'),
            self::Two => __('capell-layout-builder::widgets.common.columns_2'),
            self::Three => __('capell-layout-builder::widgets.common.columns_3'),
        };
    }
}
