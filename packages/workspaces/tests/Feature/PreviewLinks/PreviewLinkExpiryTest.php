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

it('extend action bumps expires_at by 24 hours', function (): void {
    $workspace = Workspace::factory()->create();
    $originalExpiresAt = CarbonImmutable::now()->addHour();

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now(),
        'expires_at' => $originalExpiresAt,
    ]);

    livewire(ManagePreviewLinks::class)
        ->callTableAction('extend', $link)
        ->assertHasNoTableActionErrors();

    $freshLink = PreviewLink::query()->find($link->id);
    $expectedExpiresAt = $originalExpiresAt->addMinutes(1440);

    expect($freshLink->expires_at->timestamp)->toBe($expectedExpiresAt->timestamp);
});

it('extend action leaves the token unchanged', function (): void {
    $workspace = Workspace::factory()->create();
    $originalToken = PreviewLink::generateToken();

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => $originalToken,
        'issued_at' => CarbonImmutable::now(),
        'expires_at' => CarbonImmutable::now()->addHour(),
    ]);

    livewire(ManagePreviewLinks::class)
        ->callTableAction('extend', $link)
        ->assertHasNoTableActionErrors();

    expect(PreviewLink::query()->find($link->id)->token)->toBe($originalToken);
});

it('extend action is disabled for a revoked preview link', function (): void {
    $workspace = Workspace::factory()->create();

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now()->subHour(),
        'expires_at' => CarbonImmutable::now()->addHour(),
        'revoked_at' => CarbonImmutable::now()->subMinutes(5),
    ]);

    livewire(ManagePreviewLinks::class)
        ->assertTableActionDisabled('extend', $link);
});

it('extend action is enabled for an expired but non-revoked preview link', function (): void {
    $workspace = Workspace::factory()->create();

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now()->subDays(2),
        'expires_at' => CarbonImmutable::now()->subHour(),
    ]);

    livewire(ManagePreviewLinks::class)
        ->assertTableActionEnabled('extend', $link);
});
