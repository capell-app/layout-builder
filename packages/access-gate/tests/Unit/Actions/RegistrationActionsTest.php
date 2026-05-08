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
use Capell\AccessGate\Tests\TestCase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

uses(TestCase::class);

it('stores host application registration field values', function (): void {
    Notification::fake();

    $area = Area::factory()->create();

    app(RegistrationFieldRegistry::class)->register(new TestGithubUsernameRegistrationField);

    $registration = app(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
        'github_username' => 'octocat',
    ]);

    expect($registration->field_values)->toBe([
        'github_username' => [
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

    $registration = app(CreateRegistrationAction::class)->handle(Area::factory()->create(), [
        'email' => 'mona@example.test',
    ]);

    $approved = app(ApproveRegistrationAction::class)->handle($registration, approvedByUserId: 42);

    expect($approved->status)->toBe(RegistrationStatus::Approved)
        ->and($approved->approved_at)->not->toBeNull()
        ->and(Grant::query()->where('registration_id', $approved->getKey())->first())
        ->status->toBe(GrantStatus::Active)
        ->and(ClaimToken::query()->where('registration_id', $approved->getKey())->exists())->toBeTrue()
        ->and(Event::query()->where('registration_id', $approved->getKey())->where('type', EventType::RegistrationApproved)->exists())->toBeTrue();

    expect($eventDispatched)->toBeTrue();
    Notification::assertSentOnDemand(AccessApprovedNotification::class);
});

it('auto approves registrations when the area strategy allows it', function (): void {
    Notification::fake();

    $area = Area::factory()->create([
        'approval_strategy' => ApprovalStrategy::FirstNAutoApprove,
        'approval_limit' => 1,
    ]);

    $first = app(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    $second = app(CreateRegistrationAction::class)->handle($area, [
        'email' => 'lisa@example.test',
    ]);

    expect($first->status)->toBe(RegistrationStatus::Approved)
        ->and($second->status)->toBe(RegistrationStatus::Pending);
});

it('refuses to approve rejected registrations', function (): void {
    Notification::fake();

    $registration = Registration::factory()->create([
        'status' => RegistrationStatus::Rejected,
        'rejected_at' => now(),
    ]);

    expect(fn (): mixed => app(ApproveRegistrationAction::class)->handle($registration))
        ->toThrow(LogicException::class);

    expect(Grant::query()->where('registration_id', $registration->getKey())->exists())->toBeFalse();
});

it('resends a claim link for duplicate requests from approved registrations', function (): void {
    Notification::fake();

    $area = Area::factory()->create();
    $registration = app(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    app(ApproveRegistrationAction::class)->handle($registration);
    $claimTokenCount = ClaimToken::query()->count();

    Notification::fake();

    app(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]);

    expect(ClaimToken::query()->count())->toBe($claimTokenCount + 1);
    Notification::assertSentOnDemand(AccessApprovedNotification::class);
});

it('does not accept public registrations for inactive or invite-only areas', function (array $areaAttributes): void {
    Notification::fake();

    $area = Area::factory()->create($areaAttributes);

    expect(fn (): mixed => app(CreateRegistrationAction::class)->handle($area, [
        'email' => 'mona@example.test',
    ]))->toThrow(ValidationException::class);

    expect(Registration::query()->count())->toBe(0);
})->with([
    'paused area' => [[
        'status' => AccessAreaStatus::Paused,
    ]],
    'closed area' => [[
        'status' => AccessAreaStatus::Closed,
    ]],
    'invite-only area' => [[
        'approval_strategy' => ApprovalStrategy::InviteOnly,
    ]],
]);

final class TestGithubUsernameRegistrationField implements RegistrationField
{
    public function key(): string
    {
        return 'github_username';
    }

    public function label(): string
    {
        return 'GitHub username';
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function validate(array $input): RegistrationFieldValue
    {
        $validated = Validator::make($input, [
            'github_username' => ['required', 'string', 'alpha_dash:ascii'],
        ])->validate();

        $username = strtolower((string) $validated['github_username']);

        return new RegistrationFieldValue(
            key: $this->key(),
            value: $username,
            metadata: [
                'avatar_url' => "https://avatars.example.test/{$username}.png",
            ],
        );
    }
}
