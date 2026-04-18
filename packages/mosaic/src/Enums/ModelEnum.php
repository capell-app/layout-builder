<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;

enum ModelEnum: string
{
    case Section = Section::class;

    case Widget = Widget::class;

    case WidgetAsset = WidgetAsset::class;
}
