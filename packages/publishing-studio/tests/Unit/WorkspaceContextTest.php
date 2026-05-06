<?php

declare(strict_types=1);

use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceContext;

beforeEach(function (): void {
    WorkspaceContext::clear();
});

afterEach(function (): void {
    WorkspaceContext::clear();
});

it('defaults to no workspace being active', function (): void {
    expect(WorkspaceContext::current())->toBeNull()
        ->and(WorkspaceContext::currentId())->toBeNull()
        ->and(WorkspaceContext::isInWorkspace())->toBeFalse();
});

it('can set and retrieve the current workspace', function (): void {
    $workspace = new Workspace;
    $workspace->forceFill(['id' => 42, 'name' => 'Spring Relaunch']);

    WorkspaceContext::set($workspace);

    expect(WorkspaceContext::current())->toBe($workspace)
        ->and(WorkspaceContext::currentId())->toBe(42)
        ->and(WorkspaceContext::isInWorkspace())->toBeTrue();
});

it('clear resets the state back to null', function (): void {
    $workspace = new Workspace;
    $workspace->forceFill(['id' => 1]);

    WorkspaceContext::set($workspace);
    WorkspaceContext::clear();

    expect(WorkspaceContext::current())->toBeNull()
        ->and(WorkspaceContext::isInWorkspace())->toBeFalse();
});

it('runWith swaps context only for the duration of the callback', function (): void {
    $outerWorkspace = new Workspace;
    $outerWorkspace->forceFill(['id' => 1]);

    $innerWorkspace = new Workspace;
    $innerWorkspace->forceFill(['id' => 2]);

    WorkspaceContext::set($outerWorkspace);

    $result = WorkspaceContext::runWith($innerWorkspace, fn (): ?Workspace => WorkspaceContext::current());

    expect($result)->toBe($innerWorkspace)
        ->and(WorkspaceContext::current())->toBe($outerWorkspace);
});

it('runWith restores previous context even when the callback throws', function (): void {
    $outerWorkspace = new Workspace;
    $outerWorkspace->forceFill(['id' => 7]);

    $innerWorkspace = new Workspace;
    $innerWorkspace->forceFill(['id' => 9]);

    WorkspaceContext::set($outerWorkspace);

    try {
        WorkspaceContext::runWith($innerWorkspace, function (): void {
            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
        // swallowed — we just want to confirm cleanup
    }

    expect(WorkspaceContext::current())->toBe($outerWorkspace);
});

it('runWith accepts null to scope to live', function (): void {
    $outerWorkspace = new Workspace;
    $outerWorkspace->forceFill(['id' => 5]);
    WorkspaceContext::set($outerWorkspace);

    $insideContext = WorkspaceContext::runWith(null, fn (): ?Workspace => WorkspaceContext::current());

    expect($insideContext)->toBeNull()
        ->and(WorkspaceContext::current())->toBe($outerWorkspace);
});
