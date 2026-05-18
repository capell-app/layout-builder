<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum SchemaExtenderEnum: string
{
    case Section = 'capell.section_schema.extenders';

    case LayoutContainer = 'capell.layout_container_schema.extenders';

    case LayoutBlock = 'capell.layout_block_configurator.extenders';

    case Block = 'capell.block_schema.extenders';

    case BlockAsset = 'capell.block_asset_configurator.extenders';
}
