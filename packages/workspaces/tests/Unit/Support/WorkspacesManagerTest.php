<?php

declare(strict_types=1);

use Capell\Workspaces\Events\Contracts\WorkspaceEventSubscriber;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Support\WorkspacesManager;
use Illuminate\Support\Traits\Macroable;

/**
 * Build an anonymous WorkspaceEventSubscriber implementation purely so we can
 * obtain a stable, distinct class-string for registration. The manager stores
 * class strings, never instantiates inside subscribe(), so the method bodies
 * are inert.
 */
function workspacesManagerAnonymousSubscriberClass(): string
{
    return (new class implements WorkspaceEventSubscriber
    {
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
    })::class;
}

it('returns an empty subscriber list before any registration', function (): void {
    $manager = new WorkspacesManager;

    expect($manager->getSubscribers())->toBe([]);
});

it('registers a subscriber class and reports it via hasSubscriber', function (): void {
    $manager = new WorkspacesManager;
    $subscriberClass = workspacesManagerAnonymousSubscriberClass();

    expect($manager->hasSubscriber($subscriberClass))->toBeFalse();

    $manager->subscribe($subscriberClass);

    expect($manager->hasSubscriber($subscriberClass))->toBeTrue();
});

it('returns registered subscribers as a sequentially indexed list', function (): void {
    $manager = new WorkspacesManager;
    $firstSubscriberClass = workspacesManagerAnonymousSubscriberClass();
    $secondSubscriberClass = workspacesManagerAnonymousSubscriberClass();

    $manager->subscribe($firstSubscriberClass);
    $manager->subscribe($secondSubscriberClass);

    $subscribers = $manager->getSubscribers();

    expect($subscribers)->toBe([$firstSubscriberClass, $secondSubscriberClass]);
    expect(array_keys($subscribers))->toBe([0, 1]);
});

it('deduplicates a subscriber registered more than once', function (): void {
    $manager = new WorkspacesManager;
    $subscriberClass = workspacesManagerAnonymousSubscriberClass();

    $manager->subscribe($subscriberClass);
    $manager->subscribe($subscriberClass);
    $manager->subscribe($subscriberClass);

    expect($manager->getSubscribers())->toBe([$subscriberClass]);
    expect($manager->hasSubscriber($subscriberClass))->toBeTrue();
});

it('treats different subscriber implementations as distinct entries', function (): void {
    $manager = new WorkspacesManager;
    $firstSubscriberClass = workspacesManagerAnonymousSubscriberClass();
    $secondSubscriberClass = workspacesManagerAnonymousSubscriberClass();

    $manager->subscribe($firstSubscriberClass);
    $manager->subscribe($secondSubscriberClass);

    expect($manager->hasSubscriber($firstSubscriberClass))->toBeTrue();
    expect($manager->hasSubscriber($secondSubscriberClass))->toBeTrue();
    expect($manager->getSubscribers())->toHaveCount(2);
});

it('reports unregistered subscriber classes as not present', function (): void {
    $manager = new WorkspacesManager;
    $registeredSubscriberClass = workspacesManagerAnonymousSubscriberClass();
    $unregisteredSubscriberClass = workspacesManagerAnonymousSubscriberClass();

    $manager->subscribe($registeredSubscriberClass);

    expect($manager->hasSubscriber($unregisteredSubscriberClass))->toBeFalse();
});

it('exposes the Macroable trait so consumers can extend the public surface', function (): void {
    $traits = class_uses(WorkspacesManager::class);

    expect($traits)->toContain(Macroable::class);

    WorkspacesManager::macro('countSubscribers', function (): int {
        /** @var WorkspacesManager $this */
        return count($this->getSubscribers());
    });

    $manager = new WorkspacesManager;
    $manager->subscribe(workspacesManagerAnonymousSubscriberClass());

    /** @phpstan-ignore-next-line dynamic macro call */
    expect($manager->countSubscribers())->toBe(1);

    WorkspacesManager::flushMacros();
});
