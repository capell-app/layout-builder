<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\Workflow;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\DashboardReports\BuildStaleDraftsQueryAction;
use Capell\PublishingStudio\Data\Workflow\PublishingWorkflowActionData;
use Capell\PublishingStudio\Data\Workflow\PublishingWorkflowPanelData;
use Capell\PublishingStudio\Enums\PublishingStudioPermission;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Filament\Pages\ScheduledPublishingPage;
use Capell\PublishingStudio\Filament\Pages\StaleDraftsPage;
use Capell\PublishingStudio\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceApproval;
use Capell\PublishingStudio\Models\WorkspaceFieldComment;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Capell\PublishingStudio\Support\WorkspaceSchema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class BuildPublishingWorkflowCommandCenterAction
{
    use AsAction;

    /**
     * @return list<PublishingWorkflowPanelData>
     */
    public function handle(?Authenticatable $user = null): array
    {
        if (! WorkspaceSchema::isReady() || ! $this->canViewWorkflow($user)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                fn (PublishingWorkflowPanelData $panel): PublishingWorkflowPanelData => $this->visiblePanel($panel, $user),
                [
                    $this->draftingPanel($user),
                    $this->reviewPanel($user),
                    $this->schedulingPanel($user),
                    $this->publishingRisksPanel($user),
                    $this->publishedHistoryPanel($user),
                    $this->recoveryPanel($user),
                ],
            ),
            static fn (PublishingWorkflowPanelData $panel): bool => $panel->actions !== [],
        ));
    }

    public function attentionCount(?Authenticatable $user = null): int
    {
        if (! WorkspaceSchema::isReady() || ! $this->canViewWorkflow($user)) {
            return 0;
        }

        $count = 0;

        if ($this->canViewWorkspaces($user)) {
            $count += $this->workspaceCount(WorkspaceStatusEnum::Open, $user);
            $count += $this->fieldCommentCount($user);
            $count += $this->workspaceCount(WorkspaceStatusEnum::InReview, $user);
            $count += $this->assignedReviewCount($user);
            $count += $this->approvalCount($user);
            $count += $this->versionCount($user);

            if ($this->canAccessPage($user, ScheduledPublishingPage::class)) {
                $count += $this->workspaceCount(WorkspaceStatusEnum::Scheduled, $user);
                $count += $this->scheduledEventCount($user);
                $count += $this->embargoedWorkspaceCount($user);
            }

            if ($this->canAccessPage($user, StaleDraftsPage::class)) {
                $count += $this->staleDraftCount($user);
                $count += $this->workspaceCount(WorkspaceStatusEnum::Abandoned, $user);
            }

            if ($this->canAccessResource($user, PreviewLinkResource::class)) {
                $count += $this->previewLinkCount($user);
            }

            if ($this->canSeePermission($user, PublishingStudioPermission::RollbackWorkspace->value)) {
                $count += $this->rollbackReadyVersionCount($user);
            }
        }

        return $count;
    }

    private function draftingPanel(?Authenticatable $user): PublishingWorkflowPanelData
    {
        return new PublishingWorkflowPanelData(
            key: 'drafting',
            label: (string) __('capell-publishing-studio::workflow.panels.drafting'),
            description: (string) __('capell-publishing-studio::workflow.descriptions.drafting'),
            actions: $this->canViewWorkspaces($user) ? [
                $this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.open_workspaces'),
                    count: $this->workspaceCount(WorkspaceStatusEnum::Open, $user),
                    severity: 'info',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.continue_drafting'),
                    url: $this->workspaceUrl('open'),
                ),
                $this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.field_comments'),
                    count: $this->fieldCommentCount($user),
                    severity: 'warning',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.resolve_comments'),
                    url: $this->workspaceUrl('open', ['workflow' => 'field-comments']),
                ),
            ] : [],
        );
    }

    private function reviewPanel(?Authenticatable $user): PublishingWorkflowPanelData
    {
        return new PublishingWorkflowPanelData(
            key: 'review',
            label: (string) __('capell-publishing-studio::workflow.panels.review'),
            description: (string) __('capell-publishing-studio::workflow.descriptions.review'),
            actions: $this->canViewWorkspaces($user) ? [
                $this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.awaiting_review'),
                    count: $this->workspaceCount(WorkspaceStatusEnum::InReview, $user),
                    severity: 'warning',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.review_work'),
                    url: $this->workspaceUrl('in_review'),
                ),
                $this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.assigned_to_me'),
                    count: $this->assignedReviewCount($user),
                    severity: 'warning',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.review_assigned'),
                    url: $this->workspaceUrl('in_review', ['workflow' => 'assigned-to-me']),
                ),
                $this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.approval_history'),
                    count: $this->approvalCount($user),
                    severity: 'info',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.view_history'),
                    url: $this->workspaceUrl(null, ['workflow' => 'approval-history']),
                ),
            ] : [],
        );
    }

    private function schedulingPanel(?Authenticatable $user): PublishingWorkflowPanelData
    {
        return new PublishingWorkflowPanelData(
            key: 'scheduling',
            label: (string) __('capell-publishing-studio::workflow.panels.scheduling'),
            description: (string) __('capell-publishing-studio::workflow.descriptions.scheduling'),
            actions: $this->canViewWorkspaces($user) && $this->canAccessPage($user, ScheduledPublishingPage::class) ? [
                $this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.scheduled_workspaces'),
                    count: $this->workspaceCount(WorkspaceStatusEnum::Scheduled, $user),
                    severity: 'info',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.manage_schedule'),
                    url: ScheduledPublishingPage::getUrl(),
                ),
                $this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.scheduler_events'),
                    count: $this->scheduledEventCount($user),
                    severity: 'info',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.open_scheduler'),
                    url: ScheduledPublishingPage::getUrl(),
                ),
            ] : [],
        );
    }

    private function publishingRisksPanel(?Authenticatable $user): PublishingWorkflowPanelData
    {
        return new PublishingWorkflowPanelData(
            key: 'publishing_risks',
            label: (string) __('capell-publishing-studio::workflow.panels.publishing_risks'),
            description: (string) __('capell-publishing-studio::workflow.descriptions.publishing_risks'),
            actions: $this->canViewWorkspaces($user) ? [
                ...($this->canAccessPage($user, StaleDraftsPage::class) ? [$this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.stale_drafts'),
                    count: $this->staleDraftCount($user),
                    severity: 'danger',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.review_stale'),
                    url: StaleDraftsPage::getUrl(),
                )] : []),
                ...($this->canAccessPage($user, ScheduledPublishingPage::class) ? [$this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.embargoed'),
                    count: $this->embargoedWorkspaceCount($user),
                    severity: 'warning',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.review_embargoes'),
                    url: ScheduledPublishingPage::getUrl(),
                )] : []),
            ] : [],
        );
    }

    private function publishedHistoryPanel(?Authenticatable $user): PublishingWorkflowPanelData
    {
        return new PublishingWorkflowPanelData(
            key: 'published_history',
            label: (string) __('capell-publishing-studio::workflow.panels.published_history'),
            description: (string) __('capell-publishing-studio::workflow.descriptions.published_history'),
            actions: $this->canViewWorkspaces($user) ? [
                $this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.published_versions'),
                    count: $this->versionCount($user),
                    severity: 'success',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.view_versions'),
                    url: $this->workspaceUrl(null, ['workflow' => 'published-versions']),
                ),
                ...($this->canAccessResource($user, PreviewLinkResource::class) ? [$this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.preview_links'),
                    count: $this->previewLinkCount($user),
                    severity: 'info',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.manage_preview_links'),
                    url: PreviewLinkResource::getUrl(),
                )] : []),
            ] : [],
        );
    }

    private function recoveryPanel(?Authenticatable $user): PublishingWorkflowPanelData
    {
        return new PublishingWorkflowPanelData(
            key: 'recovery',
            label: (string) __('capell-publishing-studio::workflow.panels.recovery'),
            description: (string) __('capell-publishing-studio::workflow.descriptions.recovery'),
            actions: $this->canViewWorkspaces($user) ? [
                ...($this->canSeePermission($user, PublishingStudioPermission::RollbackWorkspace->value) ? [$this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.rollback_ready_versions'),
                    count: $this->rollbackReadyVersionCount($user),
                    severity: 'warning',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.prepare_rollback'),
                    url: $this->workspaceUrl(null, ['workflow' => 'rollback-ready']),
                    permission: PublishingStudioPermission::RollbackWorkspace->value,
                )] : []),
                ...($this->canAccessPage($user, StaleDraftsPage::class) ? [$this->action(
                    label: (string) __('capell-publishing-studio::workflow.actions.abandoned_workspaces'),
                    count: $this->workspaceCount(WorkspaceStatusEnum::Abandoned, $user),
                    severity: 'info',
                    nextActionLabel: (string) __('capell-publishing-studio::workflow.next_actions.review_recovery'),
                    url: StaleDraftsPage::getUrl(),
                )] : []),
            ] : [],
        );
    }

    private function workspaceCount(WorkspaceStatusEnum $status, ?Authenticatable $user): int
    {
        return $this->visibleWorkspaceQuery($user)
            ->where('status', $status->value)
            ->count();
    }

    private function assignedReviewCount(?Authenticatable $user): int
    {
        if ($user === null) {
            return 0;
        }

        $morphClass = method_exists($user, 'getMorphClass')
            ? $user->getMorphClass()
            : $user::class;

        return WorkspaceReviewAssignment::query()
            ->whereIn('workspace_id', $this->visibleWorkspaceIdsQuery($user))
            ->where('reviewer_type', $morphClass)
            ->where('reviewer_id', $user->getAuthIdentifier())
            ->whereNull('decision')
            ->count();
    }

    private function fieldCommentCount(?Authenticatable $user): int
    {
        return WorkspaceFieldComment::query()
            ->whereIn('workspace_id', $this->visibleWorkspaceIdsQuery($user))
            ->whereNull('resolved_at')
            ->count();
    }

    private function approvalCount(?Authenticatable $user): int
    {
        return WorkspaceApproval::query()
            ->whereIn('workspace_id', $this->visibleWorkspaceIdsQuery($user))
            ->count();
    }

    private function staleDraftCount(?Authenticatable $user): int
    {
        return BuildStaleDraftsQueryAction::run()
            ->whereIn('id', $this->visibleWorkspaceIdsQuery($user))
            ->count();
    }

    private function embargoedWorkspaceCount(?Authenticatable $user): int
    {
        return $this->visibleWorkspaceQuery($user)
            ->whereNotNull('embargo_until')
            ->where('embargo_until', '>', now())
            ->count();
    }

    private function versionCount(?Authenticatable $user): int
    {
        return Version::query()
            ->whereIn('source_workspace_id', $this->visibleWorkspaceIdsQuery($user))
            ->count();
    }

    private function rollbackReadyVersionCount(?Authenticatable $user): int
    {
        return Version::query()
            ->whereIn('source_workspace_id', $this->visibleWorkspaceIdsQuery($user))
            ->whereNotNull('published_at')
            ->count();
    }

    private function previewLinkCount(?Authenticatable $user): int
    {
        return PreviewLink::query()
            ->whereIn('workspace_id', $this->visibleWorkspaceIdsQuery($user))
            ->count();
    }

    private function scheduledEventCount(?Authenticatable $user): int
    {
        $futurePagesQuery = $this->visiblePageQuery($user);

        $pageEvents = (clone $futurePagesQuery)->where('visible_from', '>', now())->count()
            + (clone $futurePagesQuery)->where('visible_until', '>', now())->count();

        $futureWorkspacesQuery = $this->visibleWorkspaceQuery($user);

        $workspaceEvents = (clone $futureWorkspacesQuery)->where('publish_at', '>', now())->count()
            + (clone $futureWorkspacesQuery)->where('unpublish_at', '>', now())->count()
            + (clone $futureWorkspacesQuery)->where('embargo_until', '>', now())->count()
            + (clone $futureWorkspacesQuery)->where('review_reminder_at', '>', now())->count();

        return $pageEvents + $workspaceEvents;
    }

    /**
     * @return Builder<Workspace>
     */
    private function visibleWorkspaceQuery(?Authenticatable $user): Builder
    {
        return Workspace::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $this->visibleWorkspaceIdsQuery($user));
    }

    /**
     * @return Builder<Page>
     */
    private function visiblePageQuery(?Authenticatable $user): Builder
    {
        $query = Page::query()->withoutGlobalScopes();

        if ($user instanceof Authenticatable && ! SiteScope::isGlobalActor($user) && method_exists($user, 'getAssignedSiteIds')) {
            $siteIds = $user->getAssignedSiteIds();

            return $siteIds->isNotEmpty()
                ? $query->whereIn('site_id', $siteIds)
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    private function visibleWorkspaceIdsQuery(?Authenticatable $user): Builder
    {
        if (! $user instanceof Authenticatable || SiteScope::isGlobalActor($user) || ! method_exists($user, 'getAssignedSiteIds')) {
            return Workspace::query()
                ->withoutGlobalScopes()
                ->select('id');
        }

        return $this->visiblePageQuery($user)
            ->select('workspace_id')
            ->where('workspace_id', '>', 0);
    }

    private function canViewWorkflow(?Authenticatable $user): bool
    {
        return $this->canSeePermission($user, PublishingStudioPermission::ViewPublishingWorkflowPage->value);
    }

    private function canViewWorkspaces(?Authenticatable $user): bool
    {
        if (! $user instanceof Authenticatable) {
            return false;
        }

        try {
            return Gate::forUser($user)->allows('viewAny', Workspace::class);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  class-string  $pageClass
     */
    private function canAccessPage(?Authenticatable $user, string $pageClass): bool
    {
        return $this->canSeePermission($user, 'View:' . class_basename($pageClass));
    }

    /**
     * @param  class-string  $resourceClass
     */
    private function canAccessResource(?Authenticatable $user, string $resourceClass): bool
    {
        if (! $user instanceof Authenticatable || ! method_exists($resourceClass, 'getModel')) {
            return false;
        }

        try {
            return Gate::forUser($user)->allows('viewAny', $resourceClass::getModel());
        } catch (Throwable) {
            return false;
        }
    }

    private function canSeePermission(?Authenticatable $user, string $permission): bool
    {
        if (! $user instanceof Authenticatable) {
            return false;
        }

        try {
            return Gate::forUser($user)->allows($permission);
        } catch (Throwable) {
            return false;
        }
    }

    private function action(
        string $label,
        int $count,
        string $severity,
        string $nextActionLabel,
        string $url,
        ?string $permission = null,
    ): PublishingWorkflowActionData {
        return new PublishingWorkflowActionData(
            label: $label,
            count: $count,
            severity: $severity,
            owner: (string) __('capell-publishing-studio::workflow.owner'),
            nextActionLabel: $nextActionLabel,
            url: $url,
            permission: $permission,
        );
    }

    private function visiblePanel(PublishingWorkflowPanelData $panel, ?Authenticatable $user): PublishingWorkflowPanelData
    {
        return new PublishingWorkflowPanelData(
            key: $panel->key,
            label: $panel->label,
            description: $panel->description,
            actions: array_values(array_filter(
                $panel->actions,
                fn (PublishingWorkflowActionData $action): bool => $action->count > 0
                    && $this->canSeeAction($action, $user),
            )),
        );
    }

    private function canSeeAction(PublishingWorkflowActionData $action, ?Authenticatable $user): bool
    {
        if ($action->permission === null) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        try {
            return Gate::forUser($user)->allows($action->permission);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, string>  $query
     */
    private function workspaceUrl(?string $activeTab = null, array $query = []): string
    {
        if ($activeTab !== null) {
            $query = ['activeTab' => $activeTab, ...$query];
        }

        return WorkspaceResource::getUrl() . ($query === [] ? '' : '?' . http_build_query($query));
    }
}
