<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum BackgroundAttachment: string implements HasLabel
{
    case Fixed = 'fixed';
    case Scroll = 'scroll';

    public function getLabel(): string
    {
        return (string) __('capell-layout-builder::form.background_' . $this->value);
    }
}
