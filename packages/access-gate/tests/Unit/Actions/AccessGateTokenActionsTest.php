<?php

declare(strict_types=1);

use Capell\AccessGate\Actions\ApproveRegistrationAction;
use Capell\AccessGate\Actions\ConsumeAccessGateClaimTokenAction;
use Capell\AccessGate\Actions\CreateAccessGateBrowserTokenAction;
use Capell\AccessGate\Actions\CreateAccessGateClaimTokenAction;
use Capell\AccessGate\Actions\CreateRegistrationAction;
use Capell\AccessGate\Enums\BrowserTokenStatus;
use Capell\AccessGate\Enums\ClaimTokenStatus;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Enums\TokenPolicy;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Models\ClaimToken;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Tests\TestCase;

uses(TestCase::class);

it('issues hashed claim tokens and consumes them once into browser tokens', function (): void {
    $registration = app(CreateRegistrationAction::class)->handle(Area::factory()->create(), [
        'email' => 'mona@example.test',
    ]);

    $approved = app(ApproveRegistrationAction::class)->handle($registration);
    $grant = Grant::query()->where('registration_id', $approved->getKey())->firstOrFail();

    $issuedClaimToken = app(CreateAccessGateClaimTokenAction::class)->handle($grant);

    expect($issuedClaimToken->plainTextToken)->not->toBe('')
        ->and(ClaimToken::query()->where('token_hash', hash('sha256', $issuedClaimToken->plainTextToken))->exists())->toBeTrue()
        ->and(ClaimToken::query()->where('token_hash', $issuedClaimToken->plainTextToken)->exists())->toBeFalse();

    $issuedBrowserToken = app(ConsumeAccessGateClaimTokenAction::class)->handle(
        plainTextToken: $issuedClaimToken->plainTextToken,
        metadata: ['ip_hash' => 'hash', 'user_agent' => 'Browser'],
    );

    expect($issuedBrowserToken)->not->toBeNull()
        ->and(BrowserToken::query()->where('token_hash', hash('sha256', $issuedBrowserToken->plainTextToken))->exists())->toBeTrue()
        ->and($issuedBrowserToken->token->metadata)->toBe(['ip_hash' => 'hash', 'user_agent' => 'Browser'])
        ->and($issuedClaimToken->token->refresh()->status)->toBe(ClaimTokenStatus::Claimed)
        ->and($approved->refresh()->status)->toBe(RegistrationStatus::Claimed)
        ->and(app(ConsumeAccessGateClaimTokenAction::class)->handle($issuedClaimToken->plainTextToken))->toBeNull();
});

it('revokes previous browser tokens when the area allows one active browser token', function (): void {
    $grant = Grant::factory()->create();

    $firstToken = app(CreateAccessGateBrowserTokenAction::class)->handle($grant);
    $secondToken = app(CreateAccessGateBrowserTokenAction::class)->handle($grant);

    expect($firstToken->token->refresh()->status)->toBe(BrowserTokenStatus::Revoked)
        ->and($secondToken->token->refresh()->status)->toBe(BrowserTokenStatus::Active);
});

it('keeps previous browser tokens active when the area allows multiple browser tokens', function (): void {
    $area = Area::factory()->create([
        'token_policy' => TokenPolicy::MultipleBrowserTokens,
    ]);
    $grant = Grant::factory()->for($area, 'area')->create();

    $firstToken = app(CreateAccessGateBrowserTokenAction::class)->handle($grant);
    $secondToken = app(CreateAccessGateBrowserTokenAction::class)->handle($grant);

    expect($firstToken->token->refresh()->status)->toBe(BrowserTokenStatus::Active)
        ->and($secondToken->token->refresh()->status)->toBe(BrowserTokenStatus::Active);
});

it('refuses to issue browser tokens for inactive grants', function (): void {
    $grant = Grant::factory()->create([
        'status' => GrantStatus::Revoked,
        'revoked_at' => now(),
    ]);

    expect(fn (): mixed => app(CreateAccessGateBrowserTokenAction::class)->handle($grant))
        ->toThrow(LogicException::class);
});

it('does not consume expired claim tokens', function (): void {
    $grant = Grant::factory()->create();
    $issuedClaimToken = app(CreateAccessGateClaimTokenAction::class)->handle(
        grant: $grant,
        expiresAt: now()->subMinute(),
    );

    $issuedBrowserToken = app(ConsumeAccessGateClaimTokenAction::class)->handle($issuedClaimToken->plainTextToken);

    expect($issuedBrowserToken)->toBeNull()
        ->and($issuedClaimToken->token->refresh()->status)->toBe(ClaimTokenStatus::Expired)
        ->and(BrowserToken::query()->count())->toBe(0);
});

it('does not consume claim tokens once the grant is revoked', function (): void {
    $grant = Grant::factory()->create();
    $issuedClaimToken = app(CreateAccessGateClaimTokenAction::class)->handle($grant);

    $grant->forceFill([
        'status' => GrantStatus::Revoked,
        'revoked_at' => now(),
    ])->save();

    $issuedBrowserToken = app(ConsumeAccessGateClaimTokenAction::class)->handle($issuedClaimToken->plainTextToken);

    expect($issuedBrowserToken)->toBeNull()
        ->and($issuedClaimToken->token->refresh()->status)->toBe(ClaimTokenStatus::Active)
        ->and(BrowserToken::query()->count())->toBe(0);
});
