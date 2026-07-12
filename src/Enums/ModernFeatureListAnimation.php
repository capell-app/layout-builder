<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernFeatureListAnimation: string implements HasLabel
{
    case FadeIn = 'fade-in';
    case SlideUp = 'slide-up';
    case Zoom = 'zoom';
    case Bounce = 'bounce';

    public function getLabel(): string
    {
        return match ($this) {
            self::FadeIn => __('capell-layout-builder::widgets.modern.feature_list.animation_fade'),
            self::SlideUp => __('capell-layout-builder::widgets.modern.feature_list.animation_slide'),
            self::Zoom => __('capell-layout-builder::widgets.modern.feature_list.animation_zoom'),
            self::Bounce => __('capell-layout-builder::widgets.modern.feature_list.animation_bounce'),
        };
    }
}
