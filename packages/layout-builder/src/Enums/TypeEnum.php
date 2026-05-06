<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Models\Section;
use Capell\LayoutBuilder\Models\Widget;

enum TypeEnum: string
{
    case Section = 'section';

    case Widget = 'widget';

    public function getModel(): string
    {
        return match ($this) {
            self::Section => Section::class,
            self::Widget => Widget::class,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Section => __('capell-layout-builder::generic.content'),
            self::Widget => __('capell-layout-builder::generic.widget')
        };
    }
}
