<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\Pages\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceApproval;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Override;

class ResubmitForReviewAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::button.resubmit_for_review'))
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->authorize(fn (Pageable $record): bool => auth()->user()?->can('update', $record) === true)
            ->visible(fn (Pageable $record): bool => $this->shouldBeVisible($record))
            ->requiresConfirmation()
            ->action(function (Pageable $record): void {
                $workspace = $this->workspace($record);

                if (! $workspace instanceof Workspace) {
                    return;
                }

                $workspace->submitForApproval(auth()->user());

                Notification::make()
                    ->title(__('capell-admin::message.resubmitted_for_review'))
                    ->success()
                    ->send();
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'resubmitForReview';
    }

    private function shouldBeVisible(Pageable $record): bool
    {
        if (method_exists($record, 'isLive') && $record->isLive()) {
            return false;
        }

        $workspace = $this->workspace($record);

        if (! $workspace instanceof Workspace) {
            return false;
        }

        $latestApproval = WorkspaceApproval::query()
            ->where('workspace_id', $workspace->id)
            ->latest('id')
            ->first();

        $latestAction = $latestApproval?->action;

        if (! $latestAction instanceof WorkspaceApprovalActionEnum) {
            return false;
        }

        return in_array($latestAction, [
            WorkspaceApprovalActionEnum::ChangesRequested,
            WorkspaceApprovalActionEnum::Rejected,
        ], true);
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
