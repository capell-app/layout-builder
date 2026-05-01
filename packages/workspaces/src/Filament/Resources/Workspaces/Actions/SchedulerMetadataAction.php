<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Actions;

use Capell\Workspaces\Actions\SetWorkspaceSchedulerMetadataAction;
use Capell\Workspaces\Models\Workspace;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Override;

class SchedulerMetadataAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-workspaces::scheduler.actions.manage'))
            ->icon(Heroicon::OutlinedCalendar)
            ->color('gray')
            ->authorize('update')
            ->visible(fn (Workspace $record): bool => ! $record->status->isTerminal())
            ->schema([
                DateTimePicker::make('unpublish_at')
                    ->label(__('capell-workspaces::scheduler.fields.unpublish_at'))
                    ->seconds(false)
                    ->default(fn (Workspace $record): ?CarbonImmutable => $record->unpublish_at),
                DateTimePicker::make('embargo_until')
                    ->label(__('capell-workspaces::scheduler.fields.embargo_until'))
                    ->seconds(false)
                    ->default(fn (Workspace $record): ?CarbonImmutable => $record->embargo_until),
                DateTimePicker::make('review_reminder_at')
                    ->label(__('capell-workspaces::scheduler.fields.review_reminder_at'))
                    ->seconds(false)
                    ->default(fn (Workspace $record): ?CarbonImmutable => $record->review_reminder_at),
            ])
            ->action(function (Workspace $record, array $data): void {
                SetWorkspaceSchedulerMetadataAction::run($record, [
                    'unpublish_at' => $data['unpublish_at'] ?? null,
                    'embargo_until' => $data['embargo_until'] ?? null,
                    'review_reminder_at' => $data['review_reminder_at'] ?? null,
                ]);

                Notification::make()
                    ->title(__('capell-workspaces::scheduler.notifications.updated'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'schedulerMetadata';
    }
}
