<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\RegisteredDraftable;
use Capell\PublishingStudio\WorkspaceRegistry;
use Illuminate\Database\Eloquent\Model;

beforeEach(function (): void {
    WorkspaceRegistry::reset();
});

afterEach(function (): void {
    WorkspaceRegistry::reset();
});

it('returns no registrations after reset', function (): void {
    expect(WorkspaceRegistry::all())->toBe([])
        ->and(WorkspaceRegistry::modelClasses())->toBe([]);
});

it('registers a model class and exposes it through the registry', function (): void {
    WorkspaceRegistry::register(Page::class);

    expect(WorkspaceRegistry::isRegistered(Page::class))->toBeTrue()
        ->and(WorkspaceRegistry::modelClasses())->toBe([Page::class])
        ->and(WorkspaceRegistry::get(Page::class))
        ->toBeInstanceOf(RegisteredDraftable::class);
});

it('registering the same model twice overwrites the previous entry', function (): void {
    $firstCloneHandler = fn (Model $source, Workspace $workspace): Model => $source;
    $secondCloneHandler = fn (Model $source, Workspace $workspace): Model => $source;

    WorkspaceRegistry::register(Page::class, $firstCloneHandler);
    WorkspaceRegistry::register(Page::class, $secondCloneHandler);

    expect(WorkspaceRegistry::modelClasses())->toBe([Page::class]);
});

it('get throws when the model is not registered', function (): void {
    expect(fn (): RegisteredDraftable => WorkspaceRegistry::get(Page::class))
        ->toThrow(RuntimeException::class);
});

it('default cloneInto replicates and stamps the workspace_id', function (): void {
    WorkspaceRegistry::register(Page::class);

    $workspace = new Workspace;
    $workspace->forceFill(['id' => 99]);

    $originalPage = new Page;
    $originalPage->setAttribute('name', 'Homepage');
    $originalPage->setAttribute('workspace_id', 0);

    $clonedPage = WorkspaceRegistry::get(Page::class)->cloneInto($originalPage, $workspace);

    expect($clonedPage)->toBeInstanceOf(Page::class)
        ->and($clonedPage->getAttribute('workspace_id'))->toBe(99)
        ->and($clonedPage->getAttribute('name'))->toBe('Homepage')
        ->and($clonedPage->exists)->toBeFalse();
});

it('custom cloneUsing callback is honoured', function (): void {
    $cloneInvocationCount = 0;
    $customCloner = function (Model $source, Workspace $workspace) use (&$cloneInvocationCount): Model {
        $cloneInvocationCount++;
        $clone = $source->replicate();
        $clone->setAttribute('name', 'Custom:' . $workspace->id);
        $clone->setAttribute('workspace_id', $workspace->id);

        return $clone;
    };

    WorkspaceRegistry::register(Page::class, cloneUsing: $customCloner);

    $workspace = new Workspace;
    $workspace->forceFill(['id' => 3]);

    $originalPage = new Page;
    $originalPage->setAttribute('name', 'Original');

    $cloned = WorkspaceRegistry::get(Page::class)->cloneInto($originalPage, $workspace);

    expect($cloneInvocationCount)->toBe(1)
        ->and($cloned->getAttribute('name'))->toBe('Custom:3');
});

it('finalizeOnPublish defaults to identity when no callback is registered', function (): void {
    WorkspaceRegistry::register(Page::class);

    $record = new Page;
    $record->setAttribute('name', 'Original');

    $finalized = WorkspaceRegistry::get(Page::class)->finalizeOnPublish($record);

    expect($finalized)->toBe($record);
});

it('finalizeOnPublish calls the registered callback when provided', function (): void {
    $finalizeInvocationCount = 0;
    $finalizer = function (Model $record) use (&$finalizeInvocationCount): Model {
        $finalizeInvocationCount++;
        $record->setAttribute('name', 'Finalized');

        return $record;
    };

    WorkspaceRegistry::register(Page::class, finalizeOnPublish: $finalizer);

    $record = new Page;
    $record->setAttribute('name', 'Pending');

    $finalized = WorkspaceRegistry::get(Page::class)->finalizeOnPublish($record);

    expect($finalizeInvocationCount)->toBe(1)
        ->and($finalized->getAttribute('name'))->toBe('Finalized');
});
