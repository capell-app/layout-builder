<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModernCardGridColumnCount: int implements HasLabel
{
    case Two = 2;
    case Three = 3;
    case Four = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::Two => __('capell-layout-builder::widgets.common.columns_2'),
            self::Three => __('capell-layout-builder::widgets.common.columns_3'),
            self::Four => __('capell-layout-builder::widgets.common.columns_4'),
        };
    }
}
