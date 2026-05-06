<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Enums;

/**
 * Stable identifiers for the editorial transitions a workspace can go
 * through. Strings are used verbatim as activitylog event names and as
 * translation-key suffixes for notification subject/body copy.
 */
enum WorkspaceTransitionEnum: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Scheduled = 'scheduled';
    case Unscheduled = 'unscheduled';
    case Published = 'published';
    case Abandoned = 'abandoned';
    case ChangesRequested = 'changes_requested';
}
