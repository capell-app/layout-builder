<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Livewire;

use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceApproval;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Renders recent approval-pipeline events (submit / approve / reject /
 * changes-requested) for a single workspace. Embedded in the workspace
 * edit form via {@see Livewire} so editors
 * can see the reviewer's feedback in the same place they revise.
 */
class WorkspaceApprovalHistory extends Component
{
    public ?int $workspaceId = null;

    public function mount(?Workspace $record = null): void
    {
        $this->workspaceId = $record?->getKey();
    }

    public function render(): View
    {
        return view('capell-admin::components.publishing-studio.approval-history', [
            'approvals' => $this->loadApprovals(),
        ]);
    }

    /** @return Collection<int, WorkspaceApproval> */
    private function loadApprovals(): Collection
    {
        if ($this->workspaceId === null) {
            return collect();
        }

        return WorkspaceApproval::query()
            ->where('workspace_id', $this->workspaceId)
            ->with('actionable')
            ->latest('id')
            ->limit(10)
            ->get();
    }
}
