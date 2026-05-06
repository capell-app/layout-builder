<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Exceptions\ReleaseWindowClosedException;
use Capell\PublishingStudio\Exceptions\StaleWorkspaceException;
use Capell\PublishingStudio\Exceptions\UrlCollisionException;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Publisher;
use Capell\PublishingStudio\Rebaser;
use Capell\PublishingStudio\RebaseReport;
use Capell\PublishingStudio\ReleaseWindowGuard;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\FormBuilder\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Facades\Auth;
use Override;

class PublishAction extends Action
{
    /**
     * @var array<int, array{collisions: array<int|string, mixed>, report: RebaseReport}>
     */
    private array $reportCache = [];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.publish'))
            ->icon(Heroicon::OutlinedRocketLaunch)
            ->color('primary')
            ->requiresConfirmation()
            ->tooltip(__('capell-admin::workspace.actions.publish_tooltip'))
            ->authorize('publish')
            ->visible(fn (Workspace $record): bool => $record->status === WorkspaceStatusEnum::Approved)
            ->modalDescription(fn (Workspace $record): string => $this->describeReport($record))
            ->schema(function (): array {
                $guard = new ReleaseWindowGuard;
                $user = Auth::user();
                $canBypass = $user instanceof AuthenticatedUser
                    && $user->can(config('capell.publishing-studio.release_windows.bypass_permission', 'publish_outside_release_window'));

                if ($guard->isOpen() || ! $canBypass) {
                    return [];
                }

                return [
                    Checkbox::make('bypass_window')
                        ->label(__('capell-admin::workspace.publish.bypass_window'))
                        ->helperText($this->windowHelperText($guard))
                        ->default(false),
                ];
            })
            ->action(function (Workspace $record, array $data): void {
                $user = Auth::user();

                if (! $user instanceof AuthenticatedUser) {
                    return;
                }

                $publisher = new Publisher;

                ['collisions' => $collisions, 'report' => $report] = $this->reportFor($record);

                if ($collisions !== [] || $report->hasConflicts()) {
                    Notification::make()
                        ->title(__('capell-admin::workspace.notifications.publish_blocked'))
                        ->body($this->describeReport($record))
                        ->danger()
                        ->send();

                    return;
                }

                $bypassWindow = (bool) ($data['bypass_window'] ?? false)
                    && $user->can(config('capell.publishing-studio.release_windows.bypass_permission', 'publish_outside_release_window'));

                try {
                    $publisher->publish($record, $user, bypassWindow: $bypassWindow);
                } catch (ReleaseWindowClosedException $exception) {
                    Notification::make()
                        ->title(__('capell-admin::workspace.notifications.publish_window_closed'))
                        ->body($exception->getMessage())
                        ->warning()
                        ->persistent()
                        ->send();

                    return;
                } catch (UrlCollisionException|StaleWorkspaceException $exception) {
                    Notification::make()
                        ->title(__('capell-admin::workspace.notifications.publish_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('capell-admin::workspace.notifications.published'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'publish';
    }

    private function windowHelperText(ReleaseWindowGuard $guard): string
    {
        $nextOpensAt = $guard->nextOpensAt();
        if (! $nextOpensAt instanceof CarbonImmutable) {
            return (string) __('capell-admin::workspace.publish.window_closed');
        }

        return (string) __('capell-admin::workspace.publish.window_next', [
            'at' => $nextOpensAt->toDateTimeString(),
        ]);
    }

    /**
     * @return array{collisions: array<int|string, mixed>, report: RebaseReport}
     */
    private function reportFor(Workspace $workspace): array
    {
        return $this->reportCache[$workspace->id] ??= [
            'collisions' => (new Publisher)->detectUrlCollisions($workspace),
            'report' => (new Rebaser)->analyse($workspace),
        ];
    }

    private function describeReport(Workspace $workspace): string
    {
        ['collisions' => $collisions, 'report' => $report] = $this->reportFor($workspace);

        if ($collisions === [] && ! $report->hasConflicts()) {
            return __('capell-admin::workspace.publish.ready');
        }

        $messages = [];

        if ($collisions !== []) {
            $messages[] = __('capell-admin::workspace.publish.url_collisions', ['count' => count($collisions)]);
        }

        if ($report->hasConflicts()) {
            $messages[] = __('capell-admin::workspace.publish.rebase_conflicts', ['count' => $report->conflictCount()]);
        }

        return implode(' ', $messages);
    }
}
