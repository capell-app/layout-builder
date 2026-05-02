<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;

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
            self::Section => __('capell-mosaic::generic.content'),
            self::Widget => __('capell-mosaic::generic.widget')
        };
    }
}
