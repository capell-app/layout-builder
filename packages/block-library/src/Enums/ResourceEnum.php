<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Capell\BlockLibrary\Filament\Resources\BlockLibrary\ContentBlockResource;

enum ResourceEnum: string
{
    case ContentBlock = ContentBlockResource::class;
}
