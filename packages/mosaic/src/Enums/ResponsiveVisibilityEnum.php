<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResponsiveVisibilityEnum: string implements HasLabel
{
    case Mobile = 'mobile';

    case Tablet = 'tablet';

    case Desktop = 'desktop';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mobile => __('capell-mosaic::form.mobile'),
            self::Tablet => __('capell-mosaic::form.tablet'),
            self::Desktop => __('capell-mosaic::form.desktop'),
        };
    }
}
