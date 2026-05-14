<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResponsiveVisibilityEnum: string implements HasLabel
{
    case Mobile = 'mobile';

    case Tablet = 'tablet';

    case Desktop = 'desktop';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mobile => __('capell-layout-builder::form.mobile'),
            self::Tablet => __('capell-layout-builder::form.tablet'),
            self::Desktop => __('capell-layout-builder::form.desktop'),
        };
    }
}
