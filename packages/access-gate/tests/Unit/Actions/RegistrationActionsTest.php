<?php

declare(strict_types=1);

use Capell\AccessGate\Actions\ApproveRegistrationAction;
use Capell\AccessGate\Actions\CreateRegistrationAction;
use Capell\AccessGate\Contracts\RegistrationField;
use Capell\AccessGate\Data\RegistrationFieldValue;
use Capell\AccessGate\Enums\ApprovalStrategy;
use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Events\RegistrationApproved;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Event;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Support\RegistrationFieldRegistry;
use Capell\AccessGate\Tests\TestCase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Validator;

uses(TestCase::class);

it('stores host application registration field values', function (): void {
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
});

it('approves a registration, creates a grant, records the event, and dispatches the approval event', function (): void {
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
        ->and(Event::query()->where('registration_id', $approved->getKey())->where('type', EventType::RegistrationApproved)->exists())->toBeTrue();

    expect($eventDispatched)->toBeTrue();
});

it('auto approves registrations when the area strategy allows it', function (): void {
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
