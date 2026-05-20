<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResponsiveLayoutPattern: string implements HasLabel
{
    case Grid = 'grid';

    case Carousel = 'carousel';

    case DesktopGridMobileCarousel = 'desktop-grid-mobile-carousel';

    public static function fromNullable(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value === null || $value === '') {
            return self::Grid;
        }

        if (! is_string($value)) {
            return self::Grid;
        }

        return self::tryFrom($value) ?? self::Grid;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Grid => __('capell-layout-builder::form.responsive_layout_pattern_grid'),
            self::Carousel => __('capell-layout-builder::form.responsive_layout_pattern_carousel'),
            self::DesktopGridMobileCarousel => __('capell-layout-builder::form.responsive_layout_pattern_desktop_grid_mobile_carousel'),
        };
    }

    public function usesMobileCarousel(): bool
    {
        return $this === self::Carousel || $this === self::DesktopGridMobileCarousel;
    }

    public function usesDesktopGrid(): bool
    {
        return $this === self::Grid || $this === self::DesktopGridMobileCarousel;
    }
}
