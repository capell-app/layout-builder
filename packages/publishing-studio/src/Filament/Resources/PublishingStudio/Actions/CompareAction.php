<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions;

use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Models\Workspace;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Route;
use Override;

class CompareAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.compare'))
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('gray')
            ->authorize('view')
            ->url(fn (Workspace $record): string => Route::has('filament.admin.resources.publishing-studio.compare')
                ? WorkspaceResource::getUrl('compare', ['record' => $record])
                : '#');
    }

    public static function getDefaultName(): ?string
    {
        return 'compare';
    }
}
