<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Enums;

enum PublishingStudioPermission: string
{
    case SubmitWorkspaceForApproval = 'submit_workspace_for_approval';
    case ApproveWorkspace = 'approve_workspace';
    case PublishWorkspace = 'publish_workspace';
    case RollbackWorkspace = 'rollback_workspace';
    case PublishOutsideReleaseWindow = 'publish_outside_release_window';
    case ViewActivityTrailPage = 'View:ActivityTrailPage';
    case ViewPublishingWorkflowPage = 'View:PublishingWorkflowPage';
    case ViewScheduledPublishingPage = 'View:ScheduledPublishingPage';
    case ViewStaleDraftsPage = 'View:StaleDraftsPage';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(
            fn (self $permission): string => $permission->value,
            self::cases(),
        );
    }
}
