<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Filament\Actions\Action;
use Filament\FormBuilder\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Facades\Auth;
use Override;

class RejectAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.reject'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->tooltip(__('capell-admin::workspace.actions.reject_tooltip'))
            ->modalDescription(__('capell-admin::workspace.actions.reject_description'))
            ->authorize('reject')
            ->visible(fn (Workspace $record): bool => $record->status === WorkspaceStatusEnum::InReview)
            ->schema([
                Textarea::make('notes')
                    ->label(__('capell-admin::workspace.fields.rejection_notes'))
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (Workspace $record, array $data): void {
                $user = Auth::user();

                if (! $user instanceof AuthenticatedUser) {
                    return;
                }

                $requiredLevels = $record->settings?->requiredApprovalLevels ?? 2;

                $record->reject($user, $requiredLevels, (string) $data['notes']);

                Notification::make()
                    ->title(__('capell-admin::workspace.notifications.rejected'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'reject';
    }
}
