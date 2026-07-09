<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImageGalleryLayout: string implements HasLabel
{
    case Grid = 'grid';
    case Carousel = 'carousel';

    public function getLabel(): string
    {
        return match ($this) {
            self::Grid => __('capell-layout-builder::form.grid'),
            self::Carousel => __('capell-layout-builder::form.carousel'),
        };
    }
}
