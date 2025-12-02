<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum SchemaExtenderEnum: string
{
    case Content = 'capell.content_schema.extenders';

    case LayoutContainer = 'capell.layout_container_schema.extenders';

    case LayoutWidget = 'capell.layout_widget_schema.extenders';

    case Widget = 'capell.widget_schema.extenders';

    case WidgetAsset = 'capell.widget_asset_schema.extenders';
}
