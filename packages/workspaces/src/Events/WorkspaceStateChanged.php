<?php

declare(strict_types=1);

namespace Capell\Workspaces\Events;

use Capell\Core\Enums\WorkspaceStatusEnum;
use Capell\Core\Models\Workspace;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a workspace transitions between editorial states (submit,
 * approve, reject, publish, abandon). Consumed by the notification listener
 * to route state-change emails to the relevant roles; also used by the
 * activitylog resolver to stamp properties onto the resulting log entry.
 */
class WorkspaceStateChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Workspace $workspace,
        public readonly WorkspaceStatusEnum $previousStatus,
        public readonly WorkspaceStatusEnum $newStatus,
        public readonly string $transition,
        public readonly ?Authenticatable $actor = null,
        public readonly ?string $notes = null,
    ) {}
}
