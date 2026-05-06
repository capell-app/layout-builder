<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Events;

use Capell\PublishingStudio\Events\Contracts\WorkspaceEventSubscriber;
use Capell\PublishingStudio\Facades\CapellPublishingStudio;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Contracts\Container\Container;

/**
 * Dispatches workspace lifecycle events to registered subscribers.
 */
class WorkspaceEventDispatcher
{
    public function __construct(private readonly Container $container) {}

    public function beforeClone(Workspace $source, Workspace $target): bool
    {
        foreach (CapellPublishingStudio::getSubscribers() as $subscriberClass) {
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
        foreach (CapellPublishingStudio::getSubscribers() as $subscriberClass) {
            /** @var WorkspaceEventSubscriber $subscriber */
            $subscriber = $this->container->make($subscriberClass);
            $subscriber->afterClone($source, $target);
        }
    }

    public function beforePublish(Workspace $workspace): bool
    {
        foreach (CapellPublishingStudio::getSubscribers() as $subscriberClass) {
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
        foreach (CapellPublishingStudio::getSubscribers() as $subscriberClass) {
            /** @var WorkspaceEventSubscriber $subscriber */
            $subscriber = $this->container->make($subscriberClass);
            $subscriber->afterPublish($workspace);
        }
    }

    public function beforeDelete(Workspace $workspace): bool
    {
        foreach (CapellPublishingStudio::getSubscribers() as $subscriberClass) {
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
        foreach (CapellPublishingStudio::getSubscribers() as $subscriberClass) {
            /** @var WorkspaceEventSubscriber $subscriber */
            $subscriber = $this->container->make($subscriberClass);
            $subscriber->afterDelete($workspace);
        }
    }
}
