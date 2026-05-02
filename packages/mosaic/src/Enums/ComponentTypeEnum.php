<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

enum ComponentTypeEnum: string
{
    case Asset = AssetComponentEnum::class;

    case Widget = WidgetComponentEnum::class;
}
