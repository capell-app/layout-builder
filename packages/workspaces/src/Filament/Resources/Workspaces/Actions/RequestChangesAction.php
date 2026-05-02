<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Actions;

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Facades\Auth;
use Override;

/**
 * Reviewer asks the editor to iterate on a workspace. Distinct from
 * {@see RejectAction} so the audit trail separates "tweak this" from
 * "this should not ship".
 */
class RequestChangesAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.request_changes'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('warning')
            ->requiresConfirmation()
            ->tooltip(__('capell-admin::workspace.actions.request_changes_tooltip'))
            ->modalHeading(__('capell-admin::workspace.actions.request_changes_modal_heading'))
            ->modalDescription(__('capell-admin::workspace.actions.request_changes_description'))
            ->authorize('reject')
            ->visible(fn (Workspace $record): bool => $record->status === WorkspaceStatusEnum::InReview)
            ->schema([
                Textarea::make('notes')
                    ->label(__('capell-admin::workspace.fields.request_changes_notes'))
                    ->placeholder(__('capell-admin::workspace.fields.request_changes_notes_placeholder'))
                    ->rows(4)
                    ->required()
                    ->minLength(3),
            ])
            ->action(function (Workspace $record, array $data): void {
                $user = Auth::user();

                if (! $user instanceof AuthenticatedUser) {
                    return;
                }

                $requiredLevels = $record->settings?->requiredApprovalLevels ?? 2;

                $record->requestChanges($user, $requiredLevels, (string) $data['notes']);

                Notification::make()
                    ->title(__('capell-admin::workspace.notifications.changes_requested'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'requestChanges';
    }
}
