<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Workspaces;

use Capell\FilamentPeek\Filament\Resources\Workspaces\Actions\WorkspacePeekPreviewAction;
use Capell\Workspaces\Contracts\WorkspaceTableActionContributor;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

final class WorkspacePeekPreviewActionContributor implements WorkspaceTableActionContributor
{
    /**
     * @return array<int, Action|ActionGroup>
     */
    public function actions(): array
    {
        return [
            WorkspacePeekPreviewAction::make(),
        ];
    }
}
