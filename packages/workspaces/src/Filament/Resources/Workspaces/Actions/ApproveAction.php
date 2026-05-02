<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Actions;

use Capell\Workspaces\Enums\WorkspaceApprovalActionEnum;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Facades\Auth;
use Override;

class ApproveAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.approve'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->tooltip(__('capell-admin::workspace.actions.approve_tooltip'))
            ->modalDescription(__('capell-admin::workspace.actions.approve_description'))
            ->authorize('approve')
            ->visible(fn (Workspace $record): bool => $record->status === WorkspaceStatusEnum::InReview)
            ->schema([
                Textarea::make('notes')
                    ->label(__('capell-admin::workspace.fields.approval_notes'))
                    ->rows(3)
                    ->nullable(),
            ])
            ->action(function (Workspace $record, array $data): void {
                $user = Auth::user();

                if (! $user instanceof AuthenticatedUser) {
                    return;
                }

                $record->approve($user, $this->nextApprovalLevel($record), $data['notes'] ?? null);

                Notification::make()
                    ->title(__('capell-admin::workspace.notifications.approved'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'approve';
    }

    private function nextApprovalLevel(Workspace $workspace): int
    {
        $highestApprovedLevel = $workspace->approvals()
            ->where('action', WorkspaceApprovalActionEnum::Approved->value)
            ->max('level');

        return ((int) $highestApprovedLevel) + 1;
    }
}
