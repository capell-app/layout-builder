<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernCtaLayout: string implements HasLabel
{
    case Centered = 'centered';
    case Split = 'split';

    public function getLabel(): string
    {
        return match ($this) {
            self::Centered => __('capell-layout-builder::widgets.modern.cta_section.layout_centered'),
            self::Split => __('capell-layout-builder::widgets.modern.cta_section.layout_split'),
        };
    }
}
