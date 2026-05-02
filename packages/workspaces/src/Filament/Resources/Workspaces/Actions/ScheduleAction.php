<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces\Actions;

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Exceptions\InvalidScheduleException;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\SchedulePublishAction;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Facades\Auth;
use Override;

class ScheduleAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.schedule'))
            ->icon(Heroicon::OutlinedCalendarDays)
            ->color('info')
            ->tooltip(__('capell-admin::workspace.actions.schedule_tooltip'))
            ->authorize('publish')
            ->visible(fn (Workspace $record): bool => in_array(
                $record->status,
                [WorkspaceStatusEnum::Approved, WorkspaceStatusEnum::Scheduled],
                true,
            ))
            ->schema([
                DateTimePicker::make('publish_at')
                    ->label(__('capell-admin::workspace.schedule.publish_at'))
                    ->required()
                    ->seconds(false)
                    ->minDate(fn (): CarbonImmutable => CarbonImmutable::now()->addMinute())
                    ->default(fn (Workspace $record): ?CarbonImmutable => $record->publish_at),
            ])
            ->action(function (Workspace $record, array $data): void {
                $user = Auth::user();

                if (! $user instanceof AuthenticatedUser) {
                    return;
                }

                try {
                    (new SchedulePublishAction)->schedule(
                        $record,
                        CarbonImmutable::parse($data['publish_at']),
                        $user,
                    );
                } catch (InvalidScheduleException $invalidScheduleException) {
                    Notification::make()
                        ->title(__('capell-admin::workspace.notifications.schedule_failed'))
                        ->body($invalidScheduleException->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('capell-admin::workspace.notifications.scheduled'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'schedule';
    }
}
