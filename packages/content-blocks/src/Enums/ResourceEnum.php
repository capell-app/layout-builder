<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Enums;

use Capell\ContentBlocks\Filament\Resources\ContentBlocks\ContentBlockResource;

enum ResourceEnum: string
{
    case ContentBlock = ContentBlockResource::class;
}
