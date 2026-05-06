<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Enums;

use Capell\PublishingStudio\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;

enum ResourceEnum: string
{
    case PreviewLink = PreviewLinkResource::class;

    case Workspace = WorkspaceResource::class;
}
