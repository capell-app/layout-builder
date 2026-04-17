<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use Capell\Admin\Concerns\HasSchemaTypes;
use Capell\Admin\Contracts\SchemaTypeEnumInterface;

enum TypeSchemaEnum: string implements SchemaTypeEnumInterface
{
    use HasSchemaTypes;

    case Content = 'Contents';

    case LayoutContainer = 'LayoutContainers';

    case LayoutWidget = 'LayoutWidgets';

    case Widget = 'Widgets';

    case WidgetAsset = 'WidgetAssets';

    public function getSchemas(): array
    {
        return match ($this) {
            self::Content => ContentSchemaEnum::cases(),
            self::LayoutContainer => LayoutContainerSchemaEnum::cases(),
            self::LayoutWidget => LayoutWidgetSchemaEnum::cases(),
            self::Widget => WidgetSchemaEnum::cases(),
            self::WidgetAsset => WidgetAssetSchemaEnum::cases(),
        };
    }
}
