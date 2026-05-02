<?php

declare(strict_types=1);

namespace Capell\Workspaces\Events\Contracts;

use Capell\Core\Support\Subscriber\Contracts\Subscriber;
use Capell\Workspaces\Models\Workspace;

/**
 * Contract for classes that subscribe to workspace lifecycle events.
 *
 * Implement this interface and register via CapellWorkspaces::subscribe()
 * to hook into workspace operations (clone, publish, approve, etc.).
 */
interface WorkspaceEventSubscriber extends Subscriber
{
    /**
     * Called before a workspace is cloned.
     * Return false to prevent the clone.
     */
    public function beforeClone(Workspace $source, Workspace $target): bool;

    /**
     * Called after a workspace is successfully cloned.
     */
    public function afterClone(Workspace $source, Workspace $target): void;

    /**
     * Called before a workspace is published.
     * Return false to prevent the publish.
     */
    public function beforePublish(Workspace $workspace): bool;

    /**
     * Called after a workspace is successfully published.
     */
    public function afterPublish(Workspace $workspace): void;

    /**
     * Called before a workspace is deleted.
     * Return false to prevent the delete.
     */
    public function beforeDelete(Workspace $workspace): bool;

    /**
     * Called after a workspace is successfully deleted.
     */
    public function afterDelete(Workspace $workspace): void;
}
