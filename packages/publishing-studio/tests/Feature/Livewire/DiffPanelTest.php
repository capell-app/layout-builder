<?php

declare(strict_types=1);

use Capell\PublishingStudio\Livewire\DiffPanel;
use Capell\PublishingStudio\Models\Workspace;

use function Pest\Livewire\livewire;

uses()->group('publishing-studio');

it('starts in side-by-side mode with unchanged hidden', function (): void {
    $workspace = Workspace::factory()->create();

    livewire(DiffPanel::class, ['workspaceId' => $workspace->id])
        ->assertSet('mode', 'side-by-side')
        ->assertSet('showUnchanged', false);
});

it('toggleMode switches to inline then back to side-by-side', function (): void {
    $workspace = Workspace::factory()->create();

    livewire(DiffPanel::class, ['workspaceId' => $workspace->id])
        ->call('toggleMode')
        ->assertSet('mode', 'inline')
        ->call('toggleMode')
        ->assertSet('mode', 'side-by-side');
});

it('toggleUnchanged flips the flag', function (): void {
    $workspace = Workspace::factory()->create();

    livewire(DiffPanel::class, ['workspaceId' => $workspace->id])
        ->assertSet('showUnchanged', false)
        ->call('toggleUnchanged')
        ->assertSet('showUnchanged', true)
        ->call('toggleUnchanged')
        ->assertSet('showUnchanged', false);
});
