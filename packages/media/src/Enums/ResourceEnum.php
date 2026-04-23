<?php

declare(strict_types=1);

namespace Capell\Media\Enums;

use Capell\Media\Filament\Resources\Media\MediaResource;

enum ResourceEnum: string
{
    case Media = MediaResource::class;
}
