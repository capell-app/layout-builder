<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Actions;

use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
use Capell\Workspaces\Models\Workspace;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
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
            ->url(fn (Workspace $record): string => WorkspaceResource::getUrl('compare', ['record' => $record]));
    }

    public static function getDefaultName(): ?string
    {
        return 'compare';
    }
}
