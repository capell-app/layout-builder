<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\SchedulePublishAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Facades\Auth;
use Override;

class UnscheduleAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.unschedule'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->authorize('publish')
            ->requiresConfirmation()
            ->tooltip(__('capell-admin::workspace.actions.unschedule_tooltip'))
            ->modalDescription(__('capell-admin::workspace.actions.unschedule_description'))
            ->visible(fn (Workspace $record): bool => $record->status === WorkspaceStatusEnum::Scheduled)
            ->action(function (Workspace $record): void {
                $user = Auth::user();

                if (! $user instanceof AuthenticatedUser) {
                    return;
                }

                (new SchedulePublishAction)->unschedule($record, $user);

                Notification::make()
                    ->title(__('capell-admin::workspace.notifications.unscheduled'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'unschedule';
    }
}
