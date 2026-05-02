<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Filament\Resources\Workspaces\Actions;

use Capell\Core\Facades\CapellCore;
use Capell\Workspaces\Actions\GenerateWorkspacePreviewUrlAction;
use Capell\Workspaces\Models\Workspace;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;
use Pboivin\FilamentPeek\Facades\Peek;

final class WorkspacePeekPreviewAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('capell-filament-peek::workspace.actions.preview_modal'))
            ->tooltip(__('capell-filament-peek::workspace.actions.preview_modal_tooltip'))
            ->icon(Heroicon::OutlinedComputerDesktop)
            ->color('gray')
            ->authorize('view')
            ->visible(fn (): bool => CapellCore::isPackageInstalled('capell-app/frontend'))
            ->action(function (): void {
                Peek::ensurePluginIsLoaded();
            })
            ->dispatch(
                'open-preview-modal',
                fn (Workspace $record): array => [
                    'modalTitle' => __('capell-filament-peek::workspace.actions.preview_modal_title'),
                    'iframeUrl' => (new GenerateWorkspacePreviewUrlAction)->handle($record),
                    'iframeContent' => null,
                ],
            );

        Peek::registerPreviewModal();
    }

    public static function getDefaultName(): string
    {
        return 'workspacePeekPreview';
    }
}
