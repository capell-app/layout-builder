<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum BackgroundPosition: string implements HasLabel
{
    case Center = 'center';
    case Top = 'top';
    case Right = 'right';
    case Bottom = 'bottom';
    case Left = 'left';
    case TopRight = 'top right';
    case TopLeft = 'top left';
    case BottomRight = 'bottom right';
    case BottomLeft = 'bottom left';

    public function getLabel(): string
    {
        return (string) __('capell-layout-builder::form.background_' . str_replace(' ', '_', $this->value));
    }
}
