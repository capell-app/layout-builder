<?php

declare(strict_types=1);

namespace Capell\Workspaces\Actions;

use Capell\Core\Models\Page;
use Capell\Workspaces\Enums\WorkspaceKindEnum;
use Capell\Workspaces\Models\Workspace;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Delete a single workspace-scoped draft row.
 *
 * When the owning workspace has no remaining draft rows after the delete,
 * every live row still shadowed by that workspace is un-shadowed so it
 * reappears in workspace context. If the workspace is a
 * {@see WorkspaceKindEnum::SinglePageDraft} and is now empty, it is deleted
 * too — those workspaces exist solely to host a single page draft.
 */
class DeletePageDraftAction
{
    use AsAction;

    public function handle(Page $draft): void
    {
        if ($draft->isLive()) {
            throw new LogicException(sprintf(
                'DeletePageDraftAction expected a workspace draft row; got live row id=%s.',
                (string) $draft->getKey(),
            ));
        }

        $workspace = $draft->workspace;

        Page::query()->withoutGlobalScopes()
            ->where('id', $draft->getKey())
            ->forceDelete();

        if ($workspace === null) {
            return;
        }

        if ($this->workspaceIsEmpty($workspace)) {
            $this->clearShadowsOwnedByWorkspace($workspace);

            if ($workspace->kind === WorkspaceKindEnum::SinglePageDraft) {
                $workspace->delete();
            }
        }
    }

    private function workspaceIsEmpty(Workspace $workspace): bool
    {
        return Page::query()->withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->doesntExist();
    }

    private function clearShadowsOwnedByWorkspace(Workspace $workspace): void
    {
        $shadowedLiveRows = Page::query()->withoutGlobalScopes()
            ->where('workspace_id', 0)
            ->where('shadowed_by_workspace_id', $workspace->id)
            ->get();

        $copyOnWrite = new CopyOnWriteAction;

        foreach ($shadowedLiveRows as $shadowedLive) {
            $copyOnWrite->clearShadow($shadowedLive, $workspace);
        }
    }
}
