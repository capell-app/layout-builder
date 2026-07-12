<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum WidgetSpacingValue: string implements HasLabel
{
    case None = 'none';
    case Small = 'sm';
    case SmallTop = 't-sm';
    case SmallBottom = 'b-sm';
    case Medium = 'md';
    case MediumTop = 't-md';
    case MediumBottom = 'b-md';
    case Large = 'lg';
    case LargeTop = 't-lg';
    case LargeBottom = 'b-lg';
    case ExtraLarge = 'xl';
    case ExtraLargeTop = 't-xl';
    case ExtraLargeBottom = 'b-xl';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => __('capell-admin::generic.none'),
            self::Small => __('capell-admin::generic.small'),
            self::SmallTop => __('capell-admin::generic.small_top'),
            self::SmallBottom => __('capell-admin::generic.small_bottom'),
            self::Medium => __('capell-admin::generic.medium'),
            self::MediumTop => __('capell-admin::generic.medium_top'),
            self::MediumBottom => __('capell-admin::generic.medium_bottom'),
            self::Large => __('capell-admin::generic.large'),
            self::LargeTop => __('capell-admin::generic.large_top'),
            self::LargeBottom => __('capell-admin::generic.large_bottom'),
            self::ExtraLarge => __('capell-admin::generic.extra_large'),
            self::ExtraLargeTop => __('capell-admin::generic.extra_large_top'),
            self::ExtraLargeBottom => __('capell-admin::generic.extra_large_bottom'),
        };
    }
}
