<?php

declare(strict_types=1);

namespace Capell\Workspaces\Livewire;

use Capell\Admin\Contracts\Extenders\PublishPanelExtender;
use Capell\Core\Models\Page;
use Capell\Workspaces\Actions\GenerateWorkspacePreviewUrlAction;
use Capell\Workspaces\Data\PagePublishStateData;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\WorkspaceContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PublishStatusPanel extends Component
{
    public int $pageId;

    #[Computed]
    public function state(): PagePublishStateData
    {
        /** @var class-string<Page> $model */
        $model = Page::class;

        /** @var Page|null $page */
        $page = $model::query()->withoutGlobalScopes()->find($this->pageId);

        if ($page === null) {
            return new PagePublishStateData(
                pageId: $this->pageId,
                isDraft: true,
                publishedAt: null,
                previewUrl: null,
                workspaceId: null,
                workspaceName: null,
                workspaceStatus: null,
            );
        }

        $activeWorkspace = WorkspaceContext::current();
        $workspace = $activeWorkspace instanceof Workspace ? $activeWorkspace : null;

        $previewUrl = null;
        if ($workspace instanceof Workspace) {
            $pageUrl = $page->pageUrl;
            $path = $pageUrl !== null ? $pageUrl->url : '/';
            $previewUrl = (new GenerateWorkspacePreviewUrlAction)->handle($workspace, $path);
        }

        return new PagePublishStateData(
            pageId: $page->id,
            isDraft: $page->workspace_id !== 0,
            publishedAt: $page->getAttribute('published_at'),
            previewUrl: $previewUrl,
            workspaceId: $workspace?->id,
            workspaceName: $workspace?->name,
            workspaceStatus: $workspace?->status,
        );
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function extensions(): array
    {
        $rendered = [];

        foreach (app()->tagged(PublishPanelExtender::TAG) as $extender) {
            /** @var PublishPanelExtender $extender */
            $result = $extender->extendPanel($this->state());

            if ($result === null) {
                continue;
            }

            $rendered[] = is_string($result) ? $result : $result->render();
        }

        return $rendered;
    }

    public function render(): View
    {
        return view('capell-admin::livewire.publish-status-panel');
    }
}
