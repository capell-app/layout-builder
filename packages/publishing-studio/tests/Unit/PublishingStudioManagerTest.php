<?php

declare(strict_types=1);

use Capell\PublishingStudio\Events\Contracts\WorkspaceEventSubscriber;
use Capell\PublishingStudio\Facades\CapellPublishingStudio;
use Capell\PublishingStudio\Models\Workspace;

test('can register subscriber via facade', function (): void {
    $subscriberClass = TestSubscriber::class;

    CapellPublishingStudio::subscribe($subscriberClass);

    expect(CapellPublishingStudio::hasSubscriber($subscriberClass))->toBeTrue();
});

test('can retrieve registered subscribers', function (): void {
    $subscriber1 = TestSubscriber::class;
    $subscriber2 = AnotherTestSubscriber::class;

    CapellPublishingStudio::subscribe($subscriber1);
    CapellPublishingStudio::subscribe($subscriber2);

    $subscribers = CapellPublishingStudio::getSubscribers();

    expect($subscribers)->toContain($subscriber1, $subscriber2);
});

test('does not register duplicate subscribers', function (): void {
    $subscriberClass = TestSubscriber::class;

    CapellPublishingStudio::subscribe($subscriberClass);
    CapellPublishingStudio::subscribe($subscriberClass);

    $subscribers = CapellPublishingStudio::getSubscribers();

    expect($subscribers)->toHaveCount(1);
});

class TestSubscriber implements WorkspaceEventSubscriber
{
    public function handle(string $event, object $context): void {}

    public function beforeClone(Workspace $source, Workspace $target): bool
    {
        return true;
    }

    public function afterClone(Workspace $source, Workspace $target): void {}

    public function beforePublish(Workspace $workspace): bool
    {
        return true;
    }

    public function afterPublish(Workspace $workspace): void {}

    public function beforeDelete(Workspace $workspace): bool
    {
        return true;
    }

    public function afterDelete(Workspace $workspace): void {}
}

class AnotherTestSubscriber implements WorkspaceEventSubscriber
{
    public function handle(string $event, object $context): void {}

    public function beforeClone(Workspace $source, Workspace $target): bool
    {
        return true;
    }

    public function afterClone(Workspace $source, Workspace $target): void {}

    public function beforePublish(Workspace $workspace): bool
    {
        return true;
    }

    public function afterPublish(Workspace $workspace): void {}

    public function beforeDelete(Workspace $workspace): bool
    {
        return true;
    }

    public function afterDelete(Workspace $workspace): void {}
}
