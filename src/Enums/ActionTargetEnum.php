<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum ActionTargetEnum: string implements HasLabel
{
    case Blank = '_blank';

    public function getLabel(): string
    {
        return __('capell-admin::generic.new_tab');
    }
}
