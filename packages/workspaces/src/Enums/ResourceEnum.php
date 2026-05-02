<?php

declare(strict_types=1);

namespace Capell\Workspaces\Enums;

use Capell\Workspaces\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;

enum ResourceEnum: string
{
    case PreviewLink = PreviewLinkResource::class;

    case Workspace = WorkspaceResource::class;
}
