<?php

declare(strict_types=1);

use Capell\Workspaces\Events\Contracts\WorkspaceEventSubscriber;
use Capell\Workspaces\Events\WorkspaceEventDispatcher;
use Capell\Workspaces\Facades\CapellWorkspaces;
use Capell\Workspaces\Models\Workspace;

test('dispatcher calls beforePublish on all subscribers', function (): void {
    $called = false;

    $subscriber = new class($called) implements WorkspaceEventSubscriber
    {
        public function __construct(public bool &$wasCalled) {}

        public function beforeClone(Workspace $source, Workspace $target): bool
        {
            return true;
        }

        public function afterClone(Workspace $source, Workspace $target): void {}

        public function beforePublish(Workspace $workspace): bool
        {
            $this->wasCalled = true;

            return true;
        }

        public function afterPublish(Workspace $workspace): void {}

        public function beforeDelete(Workspace $workspace): bool
        {
            return true;
        }

        public function afterDelete(Workspace $workspace): void {}
    };

    app()->instance($subscriber::class, $subscriber);
    CapellWorkspaces::subscribe($subscriber::class);

    $dispatcher = resolve(WorkspaceEventDispatcher::class);
    $workspace = Workspace::factory()->create();

    $result = $dispatcher->beforePublish($workspace);

    expect($result)->toBeTrue();
    expect($called)->toBeTrue();
});

test('dispatcher stops on subscriber returning false', function (): void {
    $blockingSubscriber = new class implements WorkspaceEventSubscriber
    {
        public function beforePublish(Workspace $workspace): bool
        {
            return false; // Block
        }

        public function beforeClone(Workspace $source, Workspace $target): bool
        {
            return true;
        }

        public function afterClone(Workspace $source, Workspace $target): void {}

        public function afterPublish(Workspace $workspace): void {}

        public function beforeDelete(Workspace $workspace): bool
        {
            return true;
        }

        public function afterDelete(Workspace $workspace): void {}
    };

    CapellWorkspaces::subscribe($blockingSubscriber::class);

    $dispatcher = resolve(WorkspaceEventDispatcher::class);
    $workspace = Workspace::factory()->create();

    $result = $dispatcher->beforePublish($workspace);

    expect($result)->toBeFalse();
});

test('dispatcher calls afterPublish on all subscribers', function (): void {
    $called = false;

    $subscriber = new class($called) implements WorkspaceEventSubscriber
    {
        public function __construct(public bool &$wasCalled) {}

        public function beforeClone(Workspace $source, Workspace $target): bool
        {
            return true;
        }

        public function afterClone(Workspace $source, Workspace $target): void {}

        public function beforePublish(Workspace $workspace): bool
        {
            return true;
        }

        public function afterPublish(Workspace $workspace): void
        {
            $this->wasCalled = true;
        }

        public function beforeDelete(Workspace $workspace): bool
        {
            return true;
        }

        public function afterDelete(Workspace $workspace): void {}
    };

    app()->instance($subscriber::class, $subscriber);
    CapellWorkspaces::subscribe($subscriber::class);

    $dispatcher = resolve(WorkspaceEventDispatcher::class);
    $workspace = Workspace::factory()->create();

    $dispatcher->afterPublish($workspace);

    expect($called)->toBeTrue();
});
