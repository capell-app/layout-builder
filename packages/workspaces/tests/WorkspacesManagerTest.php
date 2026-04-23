<?php

declare(strict_types=1);

use Capell\Workspaces\Facades\CapellWorkspaces;
use Capell\Workspaces\Events\Contracts\WorkspaceEventSubscriber;
use Capell\Workspaces\Models\Workspace;

test('can register subscriber via facade', function (): void {
    $subscriberClass = TestSubscriber::class;

    CapellWorkspaces::subscribe($subscriberClass);

    expect(CapellWorkspaces::hasSubscriber($subscriberClass))->toBeTrue();
});

test('can retrieve registered subscribers', function (): void {
    $subscriber1 = TestSubscriber::class;
    $subscriber2 = AnotherTestSubscriber::class;

    CapellWorkspaces::subscribe($subscriber1);
    CapellWorkspaces::subscribe($subscriber2);

    $subscribers = CapellWorkspaces::getSubscribers();

    expect($subscribers)->toContain($subscriber1, $subscriber2);
});

test('does not register duplicate subscribers', function (): void {
    $subscriberClass = TestSubscriber::class;

    CapellWorkspaces::subscribe($subscriberClass);
    CapellWorkspaces::subscribe($subscriberClass);

    $subscribers = CapellWorkspaces::getSubscribers();

    expect($subscribers)->toHaveCount(1);
});

class TestSubscriber implements WorkspaceEventSubscriber
{
    public function beforeClone(Workspace $source, Workspace $target): bool
    {
        return true;
    }

    public function afterClone(Workspace $source, Workspace $target): void
    {
    }

    public function beforePublish(Workspace $workspace): bool
    {
        return true;
    }

    public function afterPublish(Workspace $workspace): void
    {
    }

    public function beforeDelete(Workspace $workspace): bool
    {
        return true;
    }

    public function afterDelete(Workspace $workspace): void
    {
    }
}

class AnotherTestSubscriber implements WorkspaceEventSubscriber
{
    public function beforeClone(Workspace $source, Workspace $target): bool
    {
        return true;
    }

    public function afterClone(Workspace $source, Workspace $target): void
    {
    }

    public function beforePublish(Workspace $workspace): bool
    {
        return true;
    }

    public function afterPublish(Workspace $workspace): void
    {
    }

    public function beforeDelete(Workspace $workspace): bool
    {
        return true;
    }

    public function afterDelete(Workspace $workspace): void
    {
    }
}
