<?php

declare(strict_types=1);
use Capell\Workspaces\Console\Commands\PruneAbandonedWorkspacesCommand;
use Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext;
use Capell\Workspaces\Listeners\StampWorkspaceOnActivity;

it('ResolveWorkspaceContext resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(ResolveWorkspaceContext::class))->toBeTrue();
});

it('PruneAbandonedWorkspacesCommand resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(PruneAbandonedWorkspacesCommand::class))->toBeTrue();
});

it('StampWorkspaceOnActivity resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(StampWorkspaceOnActivity::class))->toBeTrue();
});
