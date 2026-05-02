<?php

declare(strict_types=1);
use Capell\Workspaces\Events\WorkspaceStateChanged;
use Capell\Workspaces\Publisher;
use Capell\Workspaces\WorkspaceContext;
use Capell\Workspaces\WorkspaceRegistry;

it('WorkspaceRegistry resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(WorkspaceRegistry::class))->toBeTrue();
});

it('WorkspaceContext resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(WorkspaceContext::class))->toBeTrue();
});

it('Publisher resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(Publisher::class))->toBeTrue();
});

it('WorkspaceStateChanged event resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(WorkspaceStateChanged::class))->toBeTrue();
});
