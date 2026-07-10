<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Filament\Support\Contracts\HasLabel;

enum BackgroundRepeat: string implements HasLabel
{
    case NoRepeat = 'no-repeat';
    case Repeat = 'repeat';
    case RepeatX = 'repeat-x';
    case RepeatY = 'repeat-y';

    public function getLabel(): string
    {
        return (string) __('capell-layout-builder::form.' . match ($this) {
            self::NoRepeat => 'repeat_once',
            self::Repeat => 'repeat_both',
            self::RepeatX => 'repeat_vertical',
            self::RepeatY => 'repeat_horizontal',
        });
    }
}
