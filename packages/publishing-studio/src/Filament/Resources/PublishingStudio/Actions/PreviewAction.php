<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions;

use Capell\Core\Facades\CapellCore;
use Capell\PublishingStudio\Actions\GenerateWorkspacePreviewUrlAction;
use Capell\PublishingStudio\Models\Workspace;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class PreviewAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.preview'))
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->authorize('view')
            ->visible(fn (): bool => CapellCore::isPackageInstalled('capell-app/frontend'))
            ->url(fn (Workspace $record): string => (new GenerateWorkspacePreviewUrlAction)->handle($record))
            ->openUrlInNewTab();
    }

    public static function getDefaultName(): ?string
    {
        return 'preview';
    }
}
