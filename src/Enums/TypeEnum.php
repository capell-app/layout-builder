<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\Core\Models\Widget;

enum TypeEnum: string
{
    case Widget = 'widget';

    public function getModel(): string
    {
        return match ($this) {
            self::Widget => Widget::class,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Widget => __('capell-layout-builder::generic.widget')
        };
    }
}
