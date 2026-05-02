<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationItemType: string implements HasLabel
{
    case Link = 'link';

    case Page = 'page';

    public function getLabel(): string
    {
        return match ($this) {
            self::Link => __('capell::generic.link'),
            self::Page => __('capell::generic.page'),
        };
    }
}
