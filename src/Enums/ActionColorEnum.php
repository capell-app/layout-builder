<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ActionColorEnum: string implements HasLabel
{
    case Primary = 'primary';
    case Secondary = 'secondary';

    public function getLabel(): string
    {
        return match ($this) {
            self::Primary => __('capell-admin::generic.primary'),
            self::Secondary => __('capell-admin::generic.secondary'),
        };
    }
}
