<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum WidgetBasicSpacingValue: string implements HasLabel
{
    case None = 'none';
    case Small = 'sm';
    case Medium = 'md';
    case Large = 'lg';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => __('capell-admin::generic.none'),
            self::Small => __('capell-admin::generic.small'),
            self::Medium => __('capell-admin::generic.medium'),
            self::Large => __('capell-admin::generic.large'),
        };
    }
}
