<?php

declare(strict_types=1);

namespace Capell\Workspaces\Livewire;

use Capell\Workspaces\Models\WorkspaceFieldComment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class FieldCommentThread extends Component
{
    public int $workspaceId;

    public string $entityType;

    public string $entityUuid;

    public string $fieldPath;

    public string $newComment = '';

    public function mount(int $workspaceId, string $entityType, string $entityUuid, string $fieldPath): void
    {
        $this->workspaceId = $workspaceId;
        $this->entityType = $entityType;
        $this->entityUuid = $entityUuid;
        $this->fieldPath = $fieldPath;
    }

    public function postComment(): void
    {
        $this->validate(['newComment' => 'required|string|max:5000']);

        $comment = new WorkspaceFieldComment([
            'workspace_id' => $this->workspaceId,
            'entity_type' => $this->entityType,
            'entity_uuid' => $this->entityUuid,
            'field_path' => $this->fieldPath,
            'body' => $this->newComment,
        ]);

        $user = auth()->user();

        if ($user !== null) {
            $comment->author()->associate($user);
        }

        $comment->save();

        $this->newComment = '';
        $this->dispatch('comment-posted');
    }

    public function resolveComment(int $commentId): void
    {
        $comment = WorkspaceFieldComment::query()->findOrFail($commentId);
        $comment->resolve();
    }

    public function reopenComment(int $commentId): void
    {
        $comment = WorkspaceFieldComment::query()->findOrFail($commentId);
        $comment->reopen();
    }

    /** @return Collection<int, WorkspaceFieldComment> */
    public function getCommentsProperty(): Collection
    {
        return WorkspaceFieldComment::query()
            ->with('author')
            ->where('workspace_id', $this->workspaceId)
            ->where('entity_type', $this->entityType)
            ->where('entity_uuid', $this->entityUuid)
            ->where('field_path', $this->fieldPath)
            ->orderByRaw('resolved_at IS NOT NULL')
            ->latest('id')
            ->get();
    }

    public function render(): View
    {
        return view('capell-workspaces::components.workspaces.field-comment-thread', [
            'comments' => $this->comments,
        ]);
    }
}
