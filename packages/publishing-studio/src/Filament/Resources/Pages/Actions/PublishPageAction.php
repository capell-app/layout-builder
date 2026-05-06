<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\Pages\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Publisher;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Override;
use Throwable;

class PublishPageAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Pageable $record): string => $this->buttonLabel($record))
            ->icon('heroicon-o-rocket-launch')
            ->color('primary')
            ->authorize(fn (Pageable $record): bool => $this->userCanPublish($record))
            ->visible(fn (Pageable $record): bool => $this->shouldBeVisible($record))
            ->disabled(fn (Pageable $record): bool => $this->shouldBeDisabled($record))
            ->requiresConfirmation()
            ->modalHeading(__('capell-admin::message.publish_heading'))
            ->modalDescription(fn (Pageable $record): HtmlString => new HtmlString($this->buildModalDescription($record)))
            ->action(function (Pageable $record): void {
                $workspace = $this->workspace($record);

                if (! $workspace instanceof Workspace) {
                    return;
                }

                try {
                    resolve(Publisher::class)->publish($workspace, auth()->user());
                } catch (Throwable $throwable) {
                    Notification::make()
                        ->title(__('capell-admin::workspace.notifications.publish_failed'))
                        ->body($throwable->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('capell-admin::message.published_notification', ['page' => $record->name]))
                    ->success()
                    ->send();

                $this->getLivewire()->redirectToLive();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'publish';
    }

    private function userCanPublish(Pageable $record): bool
    {
        $user = auth()->user();
        $workspace = $this->workspace($record);

        if ($user === null || ! $workspace instanceof Workspace) {
            return false;
        }

        return $user->can('publish', $workspace);
    }

    private function shouldBeVisible(Pageable $record): bool
    {
        if (method_exists($record, 'isLive') && $record->isLive()) {
            return false;
        }

        $status = $this->workspace($record)?->status;

        return in_array($status, [
            WorkspaceStatusEnum::Open,
            WorkspaceStatusEnum::InReview,
            WorkspaceStatusEnum::Approved,
        ], true);
    }

    private function shouldBeDisabled(Pageable $record): bool
    {
        return $this->workspace($record)?->status === WorkspaceStatusEnum::InReview;
    }

    private function buttonLabel(Pageable $record): string
    {
        $workspace = $this->workspace($record);

        if (! $workspace instanceof Workspace) {
            return __('capell-admin::button.publish');
        }

        $pageCount = Page::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->count();

        if ($pageCount > 1) {
            return __('capell-admin::button.publish_workspace_n_pages', ['count' => $pageCount]);
        }

        return __('capell-admin::button.publish');
    }

    private function buildModalDescription(Pageable $record): string
    {
        $workspace = $this->workspace($record);

        if (! $workspace instanceof Workspace) {
            return '';
        }

        if ($workspace->status === WorkspaceStatusEnum::InReview) {
            return __('capell-admin::message.publish_waiting_for_approval');
        }

        $pageCount = Page::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->count();

        if ($pageCount > 1) {
            return __('capell-admin::message.publish_multi_page', ['count' => $pageCount]);
        }

        return __('capell-admin::message.publish_single_page', ['page' => $record->name]);
    }

    private function workspace(Pageable $record): ?Workspace
    {
        $workspaceId = $record->getAttributes()['workspace_id'] ?? null;

        if ($workspaceId === null) {
            return null;
        }

        return Workspace::query()->find($workspaceId);
    }
}
