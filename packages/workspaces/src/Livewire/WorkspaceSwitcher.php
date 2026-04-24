<?php

declare(strict_types=1);

namespace Capell\Workspaces\Livewire;

use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
use Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WorkspaceSwitcher extends Component
{
    /** @var string */
    public const LAST_WORKSPACE_COOKIE = 'capell_last_workspace';

    /** @var int */
    private const LAST_WORKSPACE_COOKIE_TTL = 60 * 24 * 30;

    public function switchTo(int $workspaceId): void
    {
        $user = Auth::user();

        abort_if($user === null, 403);

        $workspace = Workspace::query()->find($workspaceId);

        if (! $workspace instanceof Workspace) {
            return;
        }

        abort_if($user->cannot('view', $workspace), 403);

        Session::put(ResolveWorkspaceContext::SESSION_KEY, $workspace->id);

        Cookie::queue(
            self::LAST_WORKSPACE_COOKIE,
            (string) $workspace->id,
            self::LAST_WORKSPACE_COOKIE_TTL,
        );

        $this->redirect(request()->header('Referer') ?? url()->current(), navigate: false);
    }

    public function returnToLive(): void
    {
        Session::forget(ResolveWorkspaceContext::SESSION_KEY);

        Cookie::queue(Cookie::forget(self::LAST_WORKSPACE_COOKIE));

        $this->redirect(request()->header('Referer') ?? url()->current(), navigate: false);
    }

    #[Computed]
    public function currentWorkspace(): ?Workspace
    {
        $workspaceId = Session::get(ResolveWorkspaceContext::SESSION_KEY);

        if (! is_int($workspaceId) && ! (is_string($workspaceId) && ctype_digit($workspaceId))) {
            return null;
        }

        return Workspace::query()->find((int) $workspaceId);
    }

    /** @return Collection<int, Workspace> */
    #[Computed]
    public function workspaces(): Collection
    {
        $user = Auth::user();

        if ($user === null || $user->cannot('viewAny', Workspace::class)) {
            /** @var Collection<int, Workspace> $empty */
            $empty = new Collection;

            return $empty;
        }

        return Workspace::query()
            ->whereIn('status', [
                WorkspaceStatusEnum::Open->value,
                WorkspaceStatusEnum::InReview->value,
                WorkspaceStatusEnum::Approved->value,
            ])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function createUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || $user->cannot('create', Workspace::class)) {
            return null;
        }

        return WorkspaceResource::getUrl('index');
    }

    public function render(): View
    {
        return view('capell-workspaces::livewire.header.workspace-switcher');
    }
}
