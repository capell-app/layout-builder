<?php

declare(strict_types=1);

namespace Capell\Tags\Enums;

use Capell\Tags\Filament\Resources\Tags\TagResource;

enum ResourceEnum: string
{
    case Tag = TagResource::class;
}
