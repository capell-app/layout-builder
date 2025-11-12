<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum TypeEnum: string
{
    case Content = 'content';
    case Widget = 'widget';

    public function getModel(): string
    {
        return match ($this) {
            self::Content => LayoutModelEnum::Content->value,
            self::Widget => LayoutModelEnum::Widget->value
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Content => __('capell-layout::generic.content'),
            self::Widget => __('capell-layout::generic.widget')
        };
    }
}
