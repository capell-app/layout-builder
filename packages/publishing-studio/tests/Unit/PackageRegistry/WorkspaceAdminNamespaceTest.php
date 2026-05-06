<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\GenerateWorkspacePreviewUrlAction;
use Capell\PublishingStudio\Filament\Pages\StaleDraftsPage;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Livewire\WorkspaceSwitcher;

it('WorkspaceResource resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(WorkspaceResource::class))->toBeTrue();
});

it('StaleDraftsPage resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(StaleDraftsPage::class))->toBeTrue();
});

it('GenerateWorkspacePreviewUrlAction resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(GenerateWorkspacePreviewUrlAction::class))->toBeTrue();
});

it('WorkspaceSwitcher resolves from Capell\\PublishingStudio namespace', function (): void {
    expect(class_exists(WorkspaceSwitcher::class))->toBeTrue();
});
