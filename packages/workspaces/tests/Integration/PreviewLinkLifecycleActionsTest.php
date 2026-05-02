<?php

declare(strict_types=1);

use Capell\Tests\Fixtures\Models\User;
use Capell\Workspaces\Actions\ExtendPreviewLinkAction;
use Capell\Workspaces\Actions\RevokePreviewLinkAction;
use Capell\Workspaces\Models\PreviewLink;
use Capell\Workspaces\Models\Workspace;
use Carbon\CarbonImmutable;

it('revoke action sets revoked_at and makes the link unusable', function (): void {
    $workspace = Workspace::factory()->create();
    $actor = User::factory()->create();

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now(),
        'expires_at' => CarbonImmutable::now()->addHour(),
    ]);

    expect($link->isRevoked())->toBeFalse()
        ->and($link->isUsable())->toBeTrue();

    $revoked = (new RevokePreviewLinkAction)->handle($link, $actor);

    expect($revoked->revoked_at)->not->toBeNull()
        ->and($revoked->isRevoked())->toBeTrue()
        ->and($revoked->isUsable())->toBeFalse();

    $fresh = PreviewLink::query()->find($link->id);
    expect($fresh->revoked_at)->not->toBeNull();
});

it('extend action adds minutes to the existing expires_at and leaves the token unchanged', function (): void {
    $workspace = Workspace::factory()->create();
    $actor = User::factory()->create();
    $originalToken = PreviewLink::generateToken();
    $originalExpiresAt = CarbonImmutable::now()->addHour();

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => $originalToken,
        'issued_at' => CarbonImmutable::now(),
        'expires_at' => $originalExpiresAt,
    ]);

    $extended = (new ExtendPreviewLinkAction)->handle($link, 30, $actor);

    $expectedExpiresAt = $originalExpiresAt->addMinutes(30);

    expect($extended->token)->toBe($originalToken)
        ->and($extended->expires_at->timestamp)->toBe($expectedExpiresAt->timestamp);

    $fresh = PreviewLink::query()->find($link->id);
    expect($fresh->expires_at->timestamp)->toBe($expectedExpiresAt->timestamp);
});

it('extend action bases the new expiry on the current expires_at, not on now', function (): void {
    $workspace = Workspace::factory()->create();
    $actor = User::factory()->create();

    $originalExpiresAt = CarbonImmutable::now()->addHours(2);

    $link = PreviewLink::query()->create([
        'workspace_id' => $workspace->id,
        'token' => PreviewLink::generateToken(),
        'issued_at' => CarbonImmutable::now(),
        'expires_at' => $originalExpiresAt,
    ]);

    (new ExtendPreviewLinkAction)->handle($link, 60, $actor);

    $fresh = PreviewLink::query()->find($link->id);

    $expectedExpiresAt = $originalExpiresAt->addMinutes(60);
    expect($fresh->expires_at->timestamp)->toBe($expectedExpiresAt->timestamp);
});
