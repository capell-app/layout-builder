<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Admin\Concerns\HasSchemaTypes;
use Capell\Admin\Contracts\SchemaTypeEnumInterface;

enum TypeSchemaEnum: string implements SchemaTypeEnumInterface
{
    use HasSchemaTypes;

    case Section = 'Sections';

    case LayoutContainer = 'LayoutContainers';

    case LayoutWidget = 'LayoutWidgets';

    case Widget = 'Widgets';

    case WidgetAsset = 'WidgetAssets';

    public function getSchemas(): array
    {
        return match ($this) {
            self::Section => SectionSchemaEnum::cases(),
            self::LayoutContainer => LayoutContainerSchemaEnum::cases(),
            self::LayoutWidget => LayoutWidgetSchemaEnum::cases(),
            self::Widget => WidgetSchemaEnum::cases(),
            self::WidgetAsset => WidgetAssetSchemaEnum::cases(),
        };
    }
}
