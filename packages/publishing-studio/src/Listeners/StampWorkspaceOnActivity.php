<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Listeners;

use Capell\PublishingStudio\WorkspaceContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

/**
 * Fires on every `eloquent.creating` of a Spatie activitylog entry and
 * stamps the currently active workspace id into the activity's properties
 * bag. Activities recorded against live (no active workspace) remain
 * unstamped so admin filters can cleanly separate them.
 */
class StampWorkspaceOnActivity
{
    public function handle(ActivityContract $activity): void
    {
        $workspaceId = WorkspaceContext::currentId();

        if ($workspaceId === null) {
            return;
        }

        if (! $activity instanceof Model) {
            return;
        }

        $currentProperties = $activity->getAttribute('properties');
        $properties = $currentProperties instanceof Collection ? $currentProperties : collect();

        $activity->setAttribute('properties', $properties->put('workspace_id', $workspaceId));
    }
}
