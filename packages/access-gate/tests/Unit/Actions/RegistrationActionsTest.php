<?php

declare(strict_types=1);

use Capell\AccessGate\Actions\ApproveRegistrationAction;
use Capell\AccessGate\Actions\CreateRegistrationAction;
use Capell\AccessGate\Contracts\RegistrationField;
use Capell\AccessGate\Data\RegistrationFieldValue;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\ApprovalStrategy;
use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\IdentityMode;
use Capell\AccessGate\Enums\RegistrationPolicy;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Events\RegistrationApproved;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\ClaimToken;
use Capell\AccessGate\Models\Event;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Notifications\AccessApprovedNotification;
use Capell\AccessGate\Notifications\AccessRequestReceivedNotification;
use Capell\AccessGate\Support\RegistrationFieldRegistry;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

it('stores host application registration field values', function (): void {
    Notification::fake();

    $area = Area::factory()->create();

    resolve(RegistrationFieldRegistry::class)->register(new TestProviderUsernameRegistrationField);

    $registration = resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
        'provider_username' => 'octocat',
    ]);

    expect($registration->field_values)->toBe([
        'provider_username' => [
            'value' => 'octocat',
            'metadata' => [
                'avatar_url' => 'https://avatars.example.test/octocat.png',
            ],
        ],
    ]);

    Notification::assertSentOnDemand(AccessRequestReceivedNotification::class);
});

it('approves a registration, creates a grant, records the event, and dispatches the approval event', function (): void {
    Notification::fake();

    $eventDispatched = false;

    EventFacade::listen(RegistrationApproved::class, function (RegistrationApproved $event) use (&$eventDispatched): void {
        $eventDispatched = true;
    });

    $registration = resolve(CreateRegistrationAction::class)->handle(Area::factory()->create(), [
        'email' => 'mona@example.test',
    ]);

    $approved = resolve(ApproveRegistrationAction::class)->handle($registration, approvedByUserId: 42);
    $grant = Grant::query()->where('registration_id', $approved->getKey())->firstOrFail();

    expect($approved->status)->toBe(RegistrationStatus::Approved)
        ->and($approved->approved_at)->not->toBeNull()
        ->and($grant->status)->toBe(GrantStatus::Active)
        ->and(ClaimToken::query()->where('registration_id', $approved->getKey())->exists())->toBeTrue()
        ->and(Event::query()->where('registration_id', $approved->getKey())->where('type', EventType::RegistrationApproved)->exists())->toBeTrue();

    expect($eventDispatched)->toBeTrue();
    Notification::assertSentOnDemand(AccessApprovedNotification::class);
});

it('uses the trusted requested host when sending claim links', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'claim_url_hosts' => ['demo.example.test'],
    ]);
    $registration = resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
        'requested_url' => 'https://demo.example.test/preview',
    ]);

    resolve(ApproveRegistrationAction::class)->handle($registration);

    Notification::assertSentOnDemand(
        AccessApprovedNotification::class,
        function (AccessApprovedNotification $notification): bool {
            $mail = $notification->toMail(new class {});

            return str_starts_with($mail->actionUrl, 'https://demo.example.test/access/claim/');
        },
    );
});

it('auto approves registrations when the area strategy allows it', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'approval_strategy' => ApprovalStrategy::FirstNAutoApprove,
        'approval_limit' => 1,
    ]);

    $first = resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    $second = resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'lisa@example.test',
    ]);

    expect($first->status)->toBe(RegistrationStatus::Approved)
        ->and($second->status)->toBe(RegistrationStatus::Pending);
});

it('allows duplicate registration requests when the area registration policy allows duplicates', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'registration_policy' => RegistrationPolicy::DuplicateAllowed,
    ]);

    resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    expect(Registration::query()->where('email_normalized', 'mona@example.test')->count())->toBe(2);
});

it('sets grant expiry and discount metadata from the access area when approving registrations', function (): void {
    Notification::fake();

    $discountExpiresAt = now()->addDays(14)->startOfSecond();
    $area = Area::factory()->create([
        'grant_duration_days' => 30,
        'discount_label' => 'Preview discount',
        'discount_code' => 'PREVIEW30',
        'discount_expires_at' => $discountExpiresAt,
        'discount_metadata' => ['source' => 'access-gate-test'],
    ]);
    $registration = resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    resolve(ApproveRegistrationAction::class)->handle($registration);

    $grant = Grant::query()->where('registration_id', $registration->getKey())->firstOrFail();

    expect($grant->expires_at)->not->toBeNull()
        ->and($grant->expires_at?->isSameDay(now()->addDays(30)))->toBeTrue()
        ->and($grant->discount_label)->toBe('Preview discount')
        ->and($grant->discount_code)->toBe('PREVIEW30')
        ->and($grant->discount_expires_at?->toDateTimeString())->toBe($discountExpiresAt->toDateTimeString())
        ->and($grant->discount_metadata)->toBe(['source' => 'access-gate-test']);
});

it('does not create guest claim tokens for authenticated-only access areas', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'identity_mode' => IdentityMode::Authenticated,
    ]);
    $registration = resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    resolve(ApproveRegistrationAction::class)->handle($registration);

    expect(ClaimToken::query()->count())->toBe(0)
        ->and(Grant::query()->where('registration_id', $registration->getKey())->exists())->toBeTrue();
});

it('refuses to approve rejected registrations', function (): void {
    Notification::fake();

    $registration = Registration::factory()->create([
        'status' => RegistrationStatus::Rejected,
        'rejected_at' => now(),
    ]);

    expect(fn (): mixed => resolve(ApproveRegistrationAction::class)->handle($registration))
        ->toThrow(LogicException::class);

    expect(Grant::query()->where('registration_id', $registration->getKey())->exists())->toBeFalse();
});

it('resends a claim link for duplicate requests from approved registrations', function (): void {
    Notification::fake();

    $area = Area::factory()->create();
    $registration = resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    resolve(ApproveRegistrationAction::class)->handle($registration);
    $claimTokenCount = ClaimToken::query()->count();

    Notification::fake();

    resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    expect(ClaimToken::query()->count())->toBe($claimTokenCount + 1);
    Notification::assertSentOnDemand(AccessApprovedNotification::class);
});

it('does not accept public registrations for closed or invite-only areas', function (array $areaAttributes): void {
    Notification::fake();

    $area = Area::factory()->create($areaAttributes);

    expect(fn (): mixed => resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]))->toThrow(ValidationException::class);

    expect(Registration::query()->count())->toBe(0);
})->with([
    'closed area' => [[
        'status' => AccessAreaStatus::Closed,
    ]],
    'invite-only area' => [[
        'approval_strategy' => ApprovalStrategy::InviteOnly,
    ]],
]);

it('accepts public registrations while an area is paused', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'status' => AccessAreaStatus::Paused,
    ]);

    $registration = resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    expect($registration->status)->toBe(RegistrationStatus::Pending);
});

it('does not accept public registrations before a scheduled access area opens', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'status' => AccessAreaStatus::Active,
        'opens_at' => now()->addHour(),
    ]);

    expect(fn (): mixed => resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]))->toThrow(ValidationException::class);

    expect(Registration::query()->count())->toBe(0);
});

it('does not accept public registrations after a scheduled access area closes', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'status' => AccessAreaStatus::Active,
        'opens_at' => now()->subHours(2),
        'closes_at' => now()->subHour(),
    ]);

    expect(fn (): mixed => resolve(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]))->toThrow(ValidationException::class);

    expect(Registration::query()->count())->toBe(0);
});

final class TestProviderUsernameRegistrationField implements RegistrationField
{
    public function key(): string
    {
        return 'provider_username';
    }

    public function label(): string
    {
        return 'Provider username';
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function validate(array $input): RegistrationFieldValue
    {
        $validated = Validator::make($input, [
            'provider_username' => ['required', 'string', 'alpha_dash:ascii'],
        ])->validate();

        $username = strtolower((string) $validated['provider_username']);

        return new RegistrationFieldValue(
            key: $this->key(),
            value: $username,
            metadata: [
                'avatar_url' => sprintf('https://avatars.example.test/%s.png', $username),
            ],
        );
    }
}
