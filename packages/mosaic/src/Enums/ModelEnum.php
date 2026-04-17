<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Mosaic\Models\Content;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;

enum ModelEnum: string
{
    case Content = Content::class;

    case Widget = Widget::class;

    case WidgetAsset = WidgetAsset::class;
}
