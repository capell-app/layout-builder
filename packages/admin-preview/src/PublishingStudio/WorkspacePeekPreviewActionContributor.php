<?php

declare(strict_types=1);

namespace Capell\AdminPreview\PublishingStudio;

use Capell\AdminPreview\Filament\Resources\PublishingStudio\Actions\WorkspacePeekPreviewAction;
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;
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
