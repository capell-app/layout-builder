<?php

declare(strict_types=1);
use Capell\Workspaces\Enums\WorkspaceKindEnum;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\PreviewLink;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Models\Workspace;

it('Workspace model resolves from Capell\\Workspaces\\Models namespace', function (): void {
    expect(class_exists(Workspace::class))->toBeTrue();
});

it('Version model resolves from Capell\\Workspaces\\Models namespace', function (): void {
    expect(class_exists(Version::class))->toBeTrue();
});

it('PreviewLink model resolves from Capell\\Workspaces\\Models namespace', function (): void {
    expect(class_exists(PreviewLink::class))->toBeTrue();
});

it('WorkspaceStatusEnum resolves from Capell\\Workspaces\\Enums namespace', function (): void {
    expect(enum_exists(WorkspaceStatusEnum::class))->toBeTrue();
});

it('WorkspaceKindEnum resolves from Capell\\Workspaces\\Enums namespace', function (): void {
    expect(enum_exists(WorkspaceKindEnum::class))->toBeTrue();
});
