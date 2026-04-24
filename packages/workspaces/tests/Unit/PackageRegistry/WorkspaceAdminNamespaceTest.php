<?php

declare(strict_types=1);

use Capell\Workspaces\Actions\GenerateWorkspacePreviewUrlAction;
use Capell\Workspaces\Filament\Pages\StaleDraftsPage;
use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
use Capell\Workspaces\Livewire\WorkspaceSwitcher;

it('WorkspaceResource resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(WorkspaceResource::class))->toBeTrue();
});

it('StaleDraftsPage resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(StaleDraftsPage::class))->toBeTrue();
});

it('GenerateWorkspacePreviewUrlAction resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(GenerateWorkspacePreviewUrlAction::class))->toBeTrue();
});

it('WorkspaceSwitcher resolves from Capell\\Workspaces namespace', function (): void {
    expect(class_exists(WorkspaceSwitcher::class))->toBeTrue();
});
