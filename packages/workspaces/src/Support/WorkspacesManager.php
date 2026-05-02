<?php

declare(strict_types=1);

namespace Capell\Workspaces\Support;

use Capell\Core\Support\Subscriber\SubscriberManager;
use Capell\Workspaces\Events\Contracts\WorkspaceEventSubscriber;
use Illuminate\Support\Traits\Macroable;

/**
 * Centralized API for extending workspace functionality.
 * Similar to CapellAdminManager in the admin package.
 *
 * @extends SubscriberManager<WorkspaceEventSubscriber>
 */
class WorkspacesManager extends SubscriberManager
{
    use Macroable;

    protected function subscriberContract(): string
    {
        return WorkspaceEventSubscriber::class;
    }
}
