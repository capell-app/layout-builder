<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum BackgroundSize: string implements HasLabel
{
    case Cover = 'cover';
    case Contain = 'contain';

    public function getLabel(): string
    {
        return (string) __('capell-layout-builder::form.background_' . $this->value);
    }
}
