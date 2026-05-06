<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions;

use Capell\PublishingStudio\Actions\SetWorkspaceSchedulerMetadataAction;
use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\FormBuilder\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Override;

class SchedulerMetadataAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-publishing-studio::scheduler.actions.manage'))
            ->icon(Heroicon::OutlinedCalendar)
            ->color('gray')
            ->authorize('update')
            ->visible(fn (Workspace $record): bool => ! $record->status->isTerminal())
            ->schema([
                DateTimePicker::make('unpublish_at')
                    ->label(__('capell-publishing-studio::scheduler.fields.takedown_reminder_at'))
                    ->seconds(false)
                    ->default(fn (Workspace $record): ?CarbonImmutable => $record->unpublish_at),
                DateTimePicker::make('embargo_until')
                    ->label(__('capell-publishing-studio::scheduler.fields.embargo_until'))
                    ->seconds(false)
                    ->default(fn (Workspace $record): ?CarbonImmutable => $record->embargo_until),
                DateTimePicker::make('review_reminder_at')
                    ->label(__('capell-publishing-studio::scheduler.fields.review_reminder_at'))
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
                    ->title(__('capell-publishing-studio::scheduler.notifications.updated'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'schedulerMetadata';
    }
}
