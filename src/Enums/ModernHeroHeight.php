<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernHeroHeight: string implements HasLabel
{
    case Small = 'sm';
    case Medium = 'md';
    case Large = 'lg';
    case ExtraLarge = 'xl';

    public function getLabel(): string
    {
        return match ($this) {
            self::Small => __('capell-layout-builder::widgets.modern.hero_banner.height_sm'),
            self::Medium => __('capell-layout-builder::widgets.modern.hero_banner.height_md'),
            self::Large => __('capell-layout-builder::widgets.modern.hero_banner.height_lg'),
            self::ExtraLarge => __('capell-layout-builder::widgets.modern.hero_banner.height_xl'),
        };
    }
}
