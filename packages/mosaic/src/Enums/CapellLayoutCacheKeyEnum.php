<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

enum CapellLayoutCacheKeyEnum: string
{
    case WidgetByKey = 'capell_layout_widget_by_key:';

    case WidgetOptions = 'capell_layout_widget_options:';
}
