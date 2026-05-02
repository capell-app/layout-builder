<?php

declare(strict_types=1);

namespace Capell\Workspaces\Livewire;

use Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\WorkspaceContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WorkspaceContextBanner extends Component
{
    public function exitToLive(): void
    {
        Session::forget(ResolveWorkspaceContext::SESSION_KEY);

        $this->redirect(request()->header('Referer') ?? url()->current(), navigate: false);
    }

    #[Computed]
    public function workspace(): ?Workspace
    {
        $workspace = WorkspaceContext::current();

        if ($workspace instanceof Workspace) {
            return $workspace;
        }

        $sessionId = Session::get(ResolveWorkspaceContext::SESSION_KEY);

        if (! is_int($sessionId) && ! (is_string($sessionId) && ctype_digit($sessionId))) {
            return null;
        }

        return Workspace::query()->find((int) $sessionId);
    }

    public function render(): View
    {
        return view('capell-workspaces::livewire.header.workspace-context-banner');
    }
}
