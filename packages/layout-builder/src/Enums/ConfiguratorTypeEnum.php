<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\Admin\Concerns\HasConfiguratorTypes;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;

enum ConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    use HasConfiguratorTypes;

    case Section = 'Sections';

    case LayoutContainer = 'LayoutContainers';

    case LayoutWidget = 'LayoutWidgets';

    case Widget = 'Widgets';

    case WidgetAsset = 'WidgetAssets';

    public function getConfigurators(): array
    {
        return match ($this) {
            self::Section => SectionConfiguratorEnum::cases(),
            self::LayoutContainer => LayoutContainerConfiguratorEnum::cases(),
            self::LayoutWidget => LayoutWidgetConfiguratorEnum::cases(),
            self::Widget => WidgetConfiguratorEnum::cases(),
            self::WidgetAsset => WidgetAssetConfiguratorEnum::cases(),
        };
    }
}
