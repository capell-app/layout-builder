<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Models;

enum LayoutModelEnum: string
{
    case Content = Models\Content::class;
    case ContentAsset = Models\ContentAsset::class;
    case Widget = Models\Widget::class;
    case WidgetAsset = Models\WidgetAsset::class;
}
