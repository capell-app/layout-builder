<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum SchemaExtenderEnum: string
{
    case Section = 'capell.section_schema.extenders';

    case LayoutContainer = 'capell.layout_container_schema.extenders';

    case LayoutBlock = 'capell.layout_block_configurator.extenders';

    case Widget = 'capell.block_schema.extenders';

    case WidgetAsset = 'capell.block_asset_configurator.extenders';
}
