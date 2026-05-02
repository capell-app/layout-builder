<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Actions;

use Capell\Workspaces\Models\Workspace;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Facades\Auth;
use Override;

class SubmitForApprovalAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.submit_for_approval'))
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('warning')
            ->requiresConfirmation()
            ->tooltip(__('capell-admin::workspace.actions.submit_for_approval_tooltip'))
            ->modalDescription(__('capell-admin::workspace.actions.submit_for_approval_description'))
            ->authorize('submitForApproval')
            ->visible(fn (Workspace $record): bool => $record->isEditable())
            ->schema([
                Textarea::make('notes')
                    ->label(__('capell-admin::workspace.fields.submission_notes'))
                    ->rows(3)
                    ->nullable(),
            ])
            ->action(function (Workspace $record, array $data): void {
                $user = Auth::user();

                if (! $user instanceof AuthenticatedUser) {
                    return;
                }

                $record->submitForApproval($user, $data['notes'] ?? null);

                Notification::make()
                    ->title(__('capell-admin::workspace.notifications.submitted'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'submitForApproval';
    }
}
