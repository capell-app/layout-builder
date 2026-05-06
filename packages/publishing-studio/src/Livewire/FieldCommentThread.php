<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Livewire;

use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceFieldComment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('view', Workspace::query()->findOrFail($workspaceId));

        $this->workspaceId = $workspaceId;
        $this->entityType = $entityType;
        $this->entityUuid = $entityUuid;
        $this->fieldPath = $fieldPath;
    }

    public function postComment(): void
    {
        $this->authorizeMutation();

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
        $this->authorizeMutation();

        $comment = $this->threadCommentQuery()->findOrFail($commentId);
        $comment->resolve();
    }

    public function reopenComment(int $commentId): void
    {
        $this->authorizeMutation();

        $comment = $this->threadCommentQuery()->findOrFail($commentId);
        $comment->reopen();
    }

    /** @return Collection<int, WorkspaceFieldComment> */
    public function getCommentsProperty(): Collection
    {
        return $this->threadCommentQuery()
            ->with('author')
            ->orderByRaw('resolved_at IS NOT NULL')
            ->latest('id')
            ->get();
    }

    public function render(): View
    {
        return view('capell-publishing-studio::components.publishing-studio.field-comment-thread', [
            'comments' => $this->comments,
        ]);
    }

    private function authorizeMutation(): void
    {
        Gate::authorize('update', Workspace::query()->findOrFail($this->workspaceId));
    }

    private function threadCommentQuery(): Builder
    {
        return WorkspaceFieldComment::query()
            ->where('workspace_id', $this->workspaceId)
            ->where('entity_type', $this->entityType)
            ->where('entity_uuid', $this->entityUuid)
            ->where('field_path', $this->fieldPath);
    }
}
