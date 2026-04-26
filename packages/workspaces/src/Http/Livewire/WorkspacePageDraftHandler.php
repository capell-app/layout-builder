<?php

declare(strict_types=1);

namespace Capell\Workspaces\Http\Livewire;

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Page;
use Capell\Workspaces\Actions\CreatePageDraftWorkspaceAction;
use Capell\Workspaces\Actions\DeletePageDraftAction;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\WorkspaceContext;
use Filament\Notifications\Notification;
use InvalidArgumentException;

class WorkspacePageDraftHandler
{
    public function saveAsDraft(EditPage $editPage): void
    {
        $editPage->saveAsDraftWithLocation(['location' => 'new']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveAsDraftWithLocation(EditPage $editPage, array $data): void
    {
        $editPage->authorize('update', $editPage->record);

        $target = match ($data['location']) {
            'new' => CreatePageDraftWorkspaceAction::run($editPage->record, auth()->user()),
            'active' => WorkspaceContext::current(),
            'other' => Workspace::query()->findOrFail($data['workspace_id']),
            default => throw new InvalidArgumentException('Unknown draft location: ' . $data['location']),
        };

        $editPage->stripCountAttributes($editPage->record);

        WorkspaceContext::runWith($target, function () use ($editPage): void {
            $editPage->save(shouldRedirect: false);
        });

        Notification::make('saved-as-draft')
            ->title(__('capell-admin::message.saved_as_draft_in', ['workspace' => $target->name]))
            ->success()
            ->send();

        $editPage->dispatch('workspace-changed', workspaceId: $target->id);
    }

    public function deletePageDraft(EditPage $editPage, int $draftId): void
    {
        $draft = Page::query()->withoutGlobalScopes()->findOrFail($draftId);
        $editPage->authorize('update', $draft);
        $workspaceName = $draft->workspace?->name ?? '—';

        DeletePageDraftAction::run($draft);

        Notification::make()
            ->title(__('capell-admin::message.draft_deleted_notification', ['workspace' => $workspaceName]))
            ->success()
            ->send();
    }

    public function countDrafts(Pageable $record): int
    {
        if (! isset($record->uuid) || $record->uuid === null || $record->uuid === '') {
            return 0;
        }

        return Page::query()
            ->withoutGlobalScopes()
            ->where('uuid', $record->uuid)
            ->where('workspace_id', '>', 0)
            ->count();
    }

    public function redirectToLive(EditPage $editPage): void
    {
        $editPage->redirect(
            $editPage::getResource()::getUrl('edit', ['record' => $editPage->record->getKey()]),
            navigate: false,
        );
    }
}
