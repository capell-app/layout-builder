<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum SchemaExtenderEnum: string
{
    case Section = 'capell.section_schema.extenders';

    case LayoutContainer = 'capell.layout_container_schema.extenders';

    case LayoutElement = 'capell.layout_element_configurator.extenders';

    case Element = 'capell.element_schema.extenders';

    case ElementAsset = 'capell.element_asset_configurator.extenders';
}
