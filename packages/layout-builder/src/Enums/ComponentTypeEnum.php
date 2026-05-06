<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum ComponentTypeEnum: string
{
    case Asset = AssetComponentEnum::class;

    case Widget = WidgetComponentEnum::class;
}
