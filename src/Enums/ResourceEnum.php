<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Resources\Blocks\BlockResource;

enum ResourceEnum: string
{
    case Block = BlockResource::class;
}
