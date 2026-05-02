<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Actions;

use Capell\Workspaces\Models\Workspace;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Override;

class SaveAsDraftAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.save_as_draft'))
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('gray')
            ->tooltip(__('capell-admin::workspace.actions.save_as_draft_tooltip'))
            ->authorize('update')
            ->visible(fn (Workspace $record): bool => $record->isEditable())
            ->action(function (Workspace $record): void {
                $record->touch();

                Notification::make()
                    ->title(__('capell-admin::workspace.notifications.saved_as_draft'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'saveAsDraft';
    }
}
