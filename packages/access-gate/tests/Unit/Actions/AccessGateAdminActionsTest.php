<?php

declare(strict_types=1);

use Capell\AccessGate\Actions\ApproveNextRegistrationsAction;
use Capell\AccessGate\Actions\CreateAccessGateClaimTokenAction;
use Capell\AccessGate\Actions\ExpireRegistrationAction;
use Capell\AccessGate\Actions\RejectRegistrationAction;
use Capell\AccessGate\Actions\RevokeAccessGateBrowserTokenRecordAction;
use Capell\AccessGate\Actions\RevokeAccessGateGrantAction;
use Capell\AccessGate\Actions\UpdateAccessGateApprovalLimitAction;
use Capell\AccessGate\Actions\UpdateAccessGateAreaStatusAction;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\BrowserTokenStatus;
use Capell\AccessGate\Enums\ClaimTokenStatus;
use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Models\ClaimToken;
use Capell\AccessGate\Models\Event;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Notifications\AccessExpiredNotification;
use Capell\AccessGate\Notifications\AccessRevokedNotification;
use Capell\AccessGate\Tests\TestCase;
use Illuminate\Support\Facades\Notification;

uses(TestCase::class);

it('rejects pending registrations and records an event', function (): void {
    $registration = Registration::factory()->create();

    $rejected = RejectRegistrationAction::run($registration, rejectedByUserId: 42);

    expect($rejected->status)->toBe(RegistrationStatus::Rejected)
        ->and($rejected->rejected_at)->not->toBeNull()
        ->and(Event::query()->where('type', EventType::RegistrationRejected)->where('user_id', 42)->exists())->toBeTrue();
});

it('does not reject registrations after they leave pending state', function (RegistrationStatus $status): void {
    $registration = Registration::factory()->create([
        'status' => $status,
        'approved_at' => $status === RegistrationStatus::Approved ? now() : null,
        'claimed_at' => $status === RegistrationStatus::Claimed ? now() : null,
        'expired_at' => $status === RegistrationStatus::Expired ? now() : null,
        'rejected_at' => $status === RegistrationStatus::Rejected ? now() : null,
    ]);

    expect(fn (): mixed => RejectRegistrationAction::run($registration))
        ->toThrow(LogicException::class);

    expect($registration->refresh()->status)->toBe($status)
        ->and(Event::query()->where('type', EventType::RegistrationRejected)->exists())->toBeFalse();
})->with([
    'approved' => RegistrationStatus::Approved,
    'claimed' => RegistrationStatus::Claimed,
    'expired' => RegistrationStatus::Expired,
    'rejected' => RegistrationStatus::Rejected,
]);

it('expires unclaimed registrations', function (): void {
    Notification::fake();

    $registration = Registration::factory()->create([
        'status' => RegistrationStatus::Approved,
        'approved_at' => now(),
    ]);
    $grant = Grant::factory()->for($registration, 'registration')->for($registration->area, 'area')->create();
    $claimToken = ClaimToken::factory()->for($registration, 'registration')->for($grant, 'grant')->for($registration->area, 'area')->create();
    $browserToken = BrowserToken::factory()->for($grant, 'grant')->for($registration->area, 'area')->create();

    $expired = ExpireRegistrationAction::run($registration, expiredByUserId: 42);

    expect($expired->status)->toBe(RegistrationStatus::Expired)
        ->and($expired->expired_at)->not->toBeNull()
        ->and($grant->refresh()->status)->toBe(GrantStatus::Expired)
        ->and($claimToken->refresh()->status)->toBe(ClaimTokenStatus::Expired)
        ->and($browserToken->refresh()->status)->toBe(BrowserTokenStatus::Expired)
        ->and(Event::query()->where('type', EventType::RegistrationExpired)->where('user_id', 42)->exists())->toBeTrue();

    Notification::assertSentOnDemand(AccessExpiredNotification::class);
});

it('does not expire claimed registrations', function (): void {
    $registration = Registration::factory()->create([
        'status' => RegistrationStatus::Claimed,
        'claimed_at' => now(),
    ]);

    expect(fn (): mixed => ExpireRegistrationAction::run($registration))
        ->toThrow(LogicException::class);

    expect($registration->refresh()->status)->toBe(RegistrationStatus::Claimed)
        ->and($registration->expired_at)->toBeNull();
});

it('revokes grants and active browser sessions', function (): void {
    Notification::fake();

    $grant = Grant::factory()->create();
    $browserToken = BrowserToken::factory()->for($grant, 'grant')->create();
    $claimToken = app(CreateAccessGateClaimTokenAction::class)->handle($grant)->token;

    $revoked = RevokeAccessGateGrantAction::run($grant, revokedByUserId: 42);

    expect($revoked->status)->toBe(GrantStatus::Revoked)
        ->and($browserToken->refresh()->status)->toBe(BrowserTokenStatus::Revoked)
        ->and($claimToken->refresh()->status)->toBe(ClaimTokenStatus::Revoked)
        ->and(Event::query()->where('type', EventType::GrantRevoked)->where('user_id', 42)->exists())->toBeTrue();

    Notification::assertSentOnDemand(AccessRevokedNotification::class);
});

it('leaves revoked grants revoked without changing the original revoked timestamp', function (): void {
    $revokedAt = now()->subDay()->startOfSecond();
    $grant = Grant::factory()->create([
        'status' => GrantStatus::Revoked,
        'revoked_at' => $revokedAt,
    ]);

    $revoked = RevokeAccessGateGrantAction::run($grant, revokedByUserId: 42);

    expect($revoked->status)->toBe(GrantStatus::Revoked)
        ->and($revoked->revoked_at?->toDateTimeString())->toBe($revokedAt->toDateTimeString())
        ->and(Event::query()->where('type', EventType::GrantRevoked)->where('user_id', 42)->exists())->toBeTrue();
});

it('revokes a browser token record and records an event', function (): void {
    $browserToken = BrowserToken::factory()->create();

    $revoked = RevokeAccessGateBrowserTokenRecordAction::run($browserToken);

    expect($revoked->status)->toBe(BrowserTokenStatus::Revoked)
        ->and($revoked->revoked_at)->not->toBeNull()
        ->and(Event::query()->where('type', EventType::BrowserTokenRevoked)->exists())->toBeTrue();
});

it('updates access area status', function (): void {
    $area = Area::factory()->create([
        'status' => AccessAreaStatus::Active,
    ]);

    $updated = UpdateAccessGateAreaStatusAction::run($area, AccessAreaStatus::Paused, updatedByUserId: 42);

    $event = Event::query()
        ->where('type', EventType::AreaStatusUpdated)
        ->where('user_id', 42)
        ->firstOrFail();

    expect($updated->status)->toBe(AccessAreaStatus::Paused)
        ->and($event->payload['previous_status'])->toBe(AccessAreaStatus::Active->value)
        ->and($event->payload['status'])->toBe(AccessAreaStatus::Paused->value);
});

it('updates approval limits and records the actor', function (): void {
    $area = Area::factory()->create([
        'approval_limit' => 10,
    ]);

    $updated = UpdateAccessGateApprovalLimitAction::run($area, 20, updatedByUserId: 42);

    expect($updated->approval_limit)->toBe(20)
        ->and(Event::query()->where('type', EventType::AreaApprovalLimitUpdated)->where('user_id', 42)->exists())->toBeTrue();
});

it('approves the next pending registrations within approval capacity', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'approval_limit' => 2,
    ]);

    $firstRegistration = Registration::factory()->for($area, 'area')->create([
        'position' => 1,
    ]);
    $secondRegistration = Registration::factory()->for($area, 'area')->create([
        'position' => 2,
    ]);
    $thirdRegistration = Registration::factory()->for($area, 'area')->create([
        'position' => 3,
    ]);

    $approved = ApproveNextRegistrationsAction::run($area, 10, approvedByUserId: 42);

    expect($approved)->toHaveCount(2)
        ->and($firstRegistration->refresh()->status)->toBe(RegistrationStatus::Approved)
        ->and($secondRegistration->refresh()->status)->toBe(RegistrationStatus::Approved)
        ->and($thirdRegistration->refresh()->status)->toBe(RegistrationStatus::Pending);
});
