<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\LayoutBuilder\Filament\Resources\Elements\ElementResource;

enum ResourceEnum: string
{
    case Element = ElementResource::class;
}
