<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum TextAlignment: string implements HasLabel
{
    case Left = 'left';
    case Right = 'right';
    case Center = 'center';

    public function getLabel(): string
    {
        return (string) __('capell-admin::generic.' . $this->value);
    }
}
