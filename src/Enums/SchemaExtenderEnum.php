<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum SchemaExtenderEnum: string
{
    case Section = 'capell.section_schema.extenders';

    case LayoutContainer = 'capell.layout_container_schema.extenders';

    case LayoutWidget = 'capell.layout_widget_configurator.extenders';

    case Widget = 'capell.widget_schema.extenders';

    case WidgetAsset = 'capell.widget_asset_configurator.extenders';
}
