<?php

declare(strict_types=1);

namespace Capell\ContentSections\Enums;

use Capell\ContentSections\Filament\Resources\Sections\SectionResource;

enum ResourceEnum: string
{
    case Section = SectionResource::class;
}
