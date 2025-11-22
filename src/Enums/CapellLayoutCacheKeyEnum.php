<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum CapellLayoutCacheKeyEnum: string
{
    case WidgetByKey = 'capell_layout_widget_by_key:';
    case WidgetOptions = 'capell_layout_widget_options:';
}
