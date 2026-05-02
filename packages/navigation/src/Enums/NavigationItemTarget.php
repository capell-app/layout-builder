<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationItemTarget: string implements HasLabel
{
    case Blank = '_blank';

    public function getLabel(): string
    {
        return match ($this) {
            self::Blank => __('capell-admin::generic.new_tab'),
        };
    }
}
