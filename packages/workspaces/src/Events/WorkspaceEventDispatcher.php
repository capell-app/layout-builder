<?php

declare(strict_types=1);

namespace Capell\Workspaces\Events;

use Capell\Workspaces\Events\Contracts\WorkspaceEventSubscriber;
use Capell\Workspaces\Facades\CapellWorkspaces;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Contracts\Container\Container;

/**
 * Dispatches workspace lifecycle events to registered subscribers.
 */
class WorkspaceEventDispatcher
{
    public function __construct(private readonly Container $container) {}

    public function beforeClone(Workspace $source, Workspace $target): bool
    {
        foreach (CapellWorkspaces::getSubscribers() as $subscriberClass) {
            /** @var WorkspaceEventSubscriber $subscriber */
            $subscriber = $this->container->make($subscriberClass);
            if ($subscriber->beforeClone($source, $target) === false) {
                return false;
            }
        }

        return true;
    }

    public function afterClone(Workspace $source, Workspace $target): void
    {
        foreach (CapellWorkspaces::getSubscribers() as $subscriberClass) {
            /** @var WorkspaceEventSubscriber $subscriber */
            $subscriber = $this->container->make($subscriberClass);
            $subscriber->afterClone($source, $target);
        }
    }

    public function beforePublish(Workspace $workspace): bool
    {
        foreach (CapellWorkspaces::getSubscribers() as $subscriberClass) {
            /** @var WorkspaceEventSubscriber $subscriber */
            $subscriber = $this->container->make($subscriberClass);
            if ($subscriber->beforePublish($workspace) === false) {
                return false;
            }
        }

        return true;
    }

    public function afterPublish(Workspace $workspace): void
    {
        foreach (CapellWorkspaces::getSubscribers() as $subscriberClass) {
            /** @var WorkspaceEventSubscriber $subscriber */
            $subscriber = $this->container->make($subscriberClass);
            $subscriber->afterPublish($workspace);
        }
    }

    public function beforeDelete(Workspace $workspace): bool
    {
        foreach (CapellWorkspaces::getSubscribers() as $subscriberClass) {
            /** @var WorkspaceEventSubscriber $subscriber */
            $subscriber = $this->container->make($subscriberClass);
            if ($subscriber->beforeDelete($workspace) === false) {
                return false;
            }
        }

        return true;
    }

    public function afterDelete(Workspace $workspace): void
    {
        foreach (CapellWorkspaces::getSubscribers() as $subscriberClass) {
            /** @var WorkspaceEventSubscriber $subscriber */
            $subscriber = $this->container->make($subscriberClass);
            $subscriber->afterDelete($workspace);
        }
    }
}
