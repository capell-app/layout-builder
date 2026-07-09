<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernImageGalleryLayout: string implements HasLabel
{
    case Grid = 'grid';
    case Masonry = 'masonry';

    public function getLabel(): string
    {
        return match ($this) {
            self::Grid => __('capell-layout-builder::widgets.modern.image_gallery.layout_grid'),
            self::Masonry => __('capell-layout-builder::widgets.modern.image_gallery.layout_masonry'),
        };
    }
}
