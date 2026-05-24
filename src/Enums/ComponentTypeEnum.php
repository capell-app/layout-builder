<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\Core\Enums\AssetComponentEnum;

enum ComponentTypeEnum: string
{
    case Asset = AssetComponentEnum::class;

    case Widget = BlockComponentEnum::class;
}
