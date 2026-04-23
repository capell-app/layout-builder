<?php

declare(strict_types=1);

namespace Capell\Workspaces\Livewire;

use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Services\WorkspaceDiffService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DiffPanel extends Component
{
    public int $workspaceId;

    public string $mode = 'side-by-side';

    public bool $showUnchanged = false;

    public function mount(int $workspaceId): void
    {
        $this->workspaceId = $workspaceId;
    }

    public function toggleMode(): void
    {
        $this->mode = $this->mode === 'side-by-side' ? 'inline' : 'side-by-side';
    }

    public function toggleUnchanged(): void
    {
        $this->showUnchanged = ! $this->showUnchanged;
    }

    public function render(): View
    {
        $workspace = Workspace::query()->findOrFail($this->workspaceId);
        $diffs = (new WorkspaceDiffService)->diffTree($workspace);

        return view('capell-admin::components.workspaces.diff-panel', [
            'diffs' => $diffs,
        ]);
    }
}
