<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum ComponentTypeEnum: string
{
    case Asset = AssetComponentEnum::class;
    case Widget = WidgetComponentEnum::class;
}
