<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

enum TypeEnum: string
{
    case Content = 'content';

    case Widget = 'widget';

    public function getModel(): string
    {
        return match ($this) {
            self::Content => ModelEnum::Section->value,
            self::Widget => ModelEnum::Widget->value
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Content => __('capell-mosaic::generic.content'),
            self::Widget => __('capell-mosaic::generic.widget')
        };
    }
}
