<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum FeatureListLayout: string implements HasLabel
{
    case Vertical = 'vertical';
    case Horizontal = 'horizontal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Vertical => __('capell-layout-builder::form.vertical'),
            self::Horizontal => __('capell-layout-builder::form.horizontal'),
        };
    }
}
