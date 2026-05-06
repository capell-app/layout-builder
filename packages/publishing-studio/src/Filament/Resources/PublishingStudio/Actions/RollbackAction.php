<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio\Actions;

use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Rollback;
use Filament\Actions\Action;
use Filament\FormBuilder\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Facades\Auth;
use LogicException;
use Override;
use Throwable;

/**
 * Emergency one-click rollback to the version immediately preceding this
 * workspace's published version. Gated by `rollback_workspace` permission
 * (release manager role). Requires a textual reason and explicit
 * confirmation.
 */
class RollbackAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::workspace.actions.rollback'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->requiresConfirmation()
            ->tooltip(__('capell-admin::workspace.actions.rollback_tooltip'))
            ->modalHeading(__('capell-admin::workspace.rollback.modal_heading'))
            ->modalDescription(__('capell-admin::workspace.rollback.modal_description'))
            ->authorize(fn (Workspace $record): bool => $this->userCanRollback($record))
            ->visible(fn (Workspace $record): bool => $this->rollbackTarget($record) instanceof Version)
            ->schema([
                Textarea::make('reason')
                    ->label(__('capell-admin::workspace.rollback.reason'))
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (Workspace $record, array $data): void {
                $user = Auth::user();

                if (! $user instanceof AuthenticatedUser) {
                    return;
                }

                $target = $this->rollbackTarget($record);

                if (! $target instanceof Version) {
                    Notification::make()
                        ->title(__('capell-admin::workspace.notifications.rollback_failed'))
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    (new Rollback)->rollbackTo($target, $user, (string) ($data['reason'] ?? ''));
                } catch (LogicException|Throwable $exception) {
                    Notification::make()
                        ->title(__('capell-admin::workspace.notifications.rollback_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('capell-admin::workspace.notifications.rolled_back'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'rollback';
    }

    private function userCanRollback(Workspace $workspace): bool
    {
        unset($workspace);

        $user = Auth::user();
        if (! $user instanceof AuthenticatedUser) {
            return false;
        }

        return $user->can('rollback_workspace');
    }

    private function rollbackTarget(Workspace $workspace): ?Version
    {
        $published = Version::query()
            ->where('source_workspace_id', $workspace->id)
            ->latest('published_at')
            ->first();

        if (! $published instanceof Version) {
            return null;
        }

        return Version::query()
            ->where('id', '<', $published->id)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->first();
    }
}
