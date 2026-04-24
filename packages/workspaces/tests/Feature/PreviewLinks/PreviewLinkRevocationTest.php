<?php

declare(strict_types=1);

use Capell\Workspaces\Filament\Resources\PreviewLinks\Pages\ManagePreviewLinks;
use Capell\Workspaces\Models\PreviewLink;
use Capell\Workspaces\Models\Workspace;
use Carbon\CarbonImmutable;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('revoke action sets revoked_at on a fresh preview link', function (): void {
    $workspace = Workspace::factory()->create();

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now(),
        'expires_at' => CarbonImmutable::now()->addHour(),
    ]);

    livewire(ManagePreviewLinks::class)
        ->callTableAction('revoke', $link)
        ->assertHasNoTableActionErrors();

    expect(PreviewLink::query()->find($link->id)->revoked_at)->not->toBeNull();
});

it('revoke action is disabled for an already-revoked preview link', function (): void {
    $workspace = Workspace::factory()->create();

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now()->subHour(),
        'expires_at' => CarbonImmutable::now()->addHour(),
        'revoked_at' => CarbonImmutable::now()->subMinutes(5),
    ]);

    livewire(ManagePreviewLinks::class)
        ->assertTableActionDisabled('revoke', $link);
});

it('revoke action is disabled for an expired preview link', function (): void {
    $workspace = Workspace::factory()->create();

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now()->subDays(2),
        'expires_at' => CarbonImmutable::now()->subHour(),
    ]);

    livewire(ManagePreviewLinks::class)
        ->assertTableActionDisabled('revoke', $link);
});
