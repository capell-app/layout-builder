<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Livewire;

use Capell\Core\Contracts\Pageable;
use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceApproval;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class PageApprovalStatus extends Widget
{
    public ?Pageable $record = null;

    protected string $view = 'capell-admin::livewire.page-approval-status';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full'];

    public function render(): View
    {
        $workspace = $this->record?->workspace;
        $approvals = $this->approvalsFor($workspace);
        $latestAction = $approvals->first()?->action;

        return view($this->view, [
            'workspace' => $workspace,
            'visible' => $this->isVisibleFor($workspace, $latestAction),
            'title' => $this->titleFor($workspace?->status, $latestAction),
            'approvals' => $approvals,
        ]);
    }

    private function isVisibleFor(?Workspace $workspace, ?WorkspaceApprovalActionEnum $latestAction): bool
    {
        if (! $workspace instanceof Workspace) {
            return false;
        }

        if (in_array($workspace->status, [
            WorkspaceStatusEnum::InReview,
            WorkspaceStatusEnum::Approved,
        ], true)) {
            return true;
        }

        return $latestAction === WorkspaceApprovalActionEnum::ChangesRequested
            || $latestAction === WorkspaceApprovalActionEnum::Rejected;
    }

    private function titleFor(?WorkspaceStatusEnum $status, ?WorkspaceApprovalActionEnum $latestAction): string
    {
        if ($status === WorkspaceStatusEnum::InReview) {
            return __('capell-admin::workspace.approval_panel.in_review_title');
        }

        if ($status === WorkspaceStatusEnum::Approved) {
            return __('capell-admin::workspace.approval_panel.approved_title');
        }

        return match ($latestAction) {
            WorkspaceApprovalActionEnum::ChangesRequested => __('capell-admin::workspace.approval_panel.changes_requested_title'),
            WorkspaceApprovalActionEnum::Rejected => __('capell-admin::workspace.approval_panel.rejected_title'),
            default => '',
        };
    }

    /** @return Collection<int, WorkspaceApproval> */
    private function approvalsFor(?Workspace $workspace): Collection
    {
        if (! $workspace instanceof Workspace) {
            return collect();
        }

        return WorkspaceApproval::query()
            ->where('workspace_id', $workspace->id)
            ->with('actionable')
            ->latest('id')
            ->limit(5)
            ->get();
    }
}
