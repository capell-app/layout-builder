<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernTestimonialsDisplayMode: string implements HasLabel
{
    case Grid = 'grid';
    case Carousel = 'carousel';

    public function getLabel(): string
    {
        return match ($this) {
            self::Grid => __('capell-layout-builder::widgets.modern.testimonials.display_mode_grid'),
            self::Carousel => __('capell-layout-builder::widgets.modern.testimonials.display_mode_carousel'),
        };
    }
}
