<?php

declare(strict_types=1);

use Capell\AccessGate\Actions\SubmitAccessGatePublicAction;
use Capell\AccessGate\Contracts\RegistrationField;
use Capell\AccessGate\Data\RegistrationFieldValue;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\ApprovalStrategy;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Support\RegistrationFieldRegistry;
use Capell\PublicActions\Data\PublicActionMetadataData;
use Capell\PublicActions\Data\PublicActionPayloadData;
use Capell\PublicActions\Data\PublicActionSubmissionData;
use Capell\PublicActions\Support\PublicActionHandlerRegistry;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

it('creates an access gate registration from a public action submission', function (): void {
    Notification::fake();

    Area::factory()->create(['key' => 'preview']);

    $result = resolve(SubmitAccessGatePublicAction::class)->handle(accessGatePublicActionSubmission([
        'area' => 'preview',
        'email' => 'mona@example.test',
        'requested_url' => 'https://example.test/preview',
    ]));

    $registration = Registration::query()->firstOrFail();

    expect($result->success)->toBeTrue()
        ->and($result->createdModelType)->toBe(Registration::class)
        ->and($result->createdModelId)->toBe((string) $registration->getKey())
        ->and($registration->email_normalized)->toBe('mona@example.test')
        ->and($registration->requested_url)->toBe('https://example.test/preview')
        ->and($registration->metadata['ip_hash'] ?? null)->toBe('hashed-ip')
        ->and($registration->metadata['source_type'] ?? null)->toBe('public_action');
});

it('returns the existing single-per-email registration for duplicate submissions', function (): void {
    Notification::fake();

    Area::factory()->create(['key' => 'preview']);

    resolve(SubmitAccessGatePublicAction::class)->handle(accessGatePublicActionSubmission([
        'area' => 'preview',
        'email' => 'mona@example.test',
    ]));
    $second = resolve(SubmitAccessGatePublicAction::class)->handle(accessGatePublicActionSubmission([
        'area' => 'preview',
        'email' => 'mona@example.test',
    ]));

    expect(Registration::query()->count())->toBe(1)
        ->and($second->createdModelId)->toBe((string) Registration::query()->firstOrFail()->getKey());
});

it('rejects closed and invite-only access areas', function (array $areaAttributes): void {
    Notification::fake();

    Area::factory()->create(['key' => 'preview', ...$areaAttributes]);

    expect(fn (): mixed => resolve(SubmitAccessGatePublicAction::class)->handle(accessGatePublicActionSubmission([
        'area' => 'preview',
        'email' => 'mona@example.test',
    ])))->toThrow(ValidationException::class);

    expect(Registration::query()->count())->toBe(0);
})->with([
    'closed area' => [[
        'status' => AccessAreaStatus::Closed,
    ]],
    'invite-only area' => [[
        'approval_strategy' => ApprovalStrategy::InviteOnly,
    ]],
]);

it('passes registered access gate field values through to registration creation', function (): void {
    Notification::fake();

    Area::factory()->create(['key' => 'preview']);
    resolve(RegistrationFieldRegistry::class)->register(new PublicActionProviderUsernameRegistrationField);

    resolve(SubmitAccessGatePublicAction::class)->handle(accessGatePublicActionSubmission([
        'area' => 'preview',
        'email' => 'mona@example.test',
        'provider_username' => 'OctoCat',
    ]));

    $registration = Registration::query()->firstOrFail();

    expect($registration->field_values['provider_username']['value'])->toBe('octocat')
        ->and($registration->metadata['field_keys'])->toBe(['provider_username']);
});

it('does not trust public action payload user ids', function (): void {
    Notification::fake();

    Area::factory()->create(['key' => 'preview']);

    resolve(SubmitAccessGatePublicAction::class)->handle(accessGatePublicActionSubmission([
        'area' => 'preview',
        'email' => 'mona@example.test',
        'user_id' => 123,
    ]));

    expect(Registration::query()->firstOrFail()->user_id)->toBeNull();
});

it('registers the access gate public action handler when public actions is available', function (): void {
    $this->app->singleton(PublicActionHandlerRegistry::class);

    $handler = resolve(PublicActionHandlerRegistry::class)->resolve('access-gate.request');

    expect($handler)->toBeInstanceOf(SubmitAccessGatePublicAction::class);
});

/**
 * @param  array<string, mixed>  $payload
 */
function accessGatePublicActionSubmission(array $payload): PublicActionSubmissionData
{
    return new PublicActionSubmissionData(
        actionKey: 'access-gate.request',
        payload: new PublicActionPayloadData($payload),
        metadata: new PublicActionMetadataData(
            ipHash: 'hashed-ip',
            userAgent: 'AccessGatePublicActionTest/1.0',
            url: 'https://example.test/landing',
        ),
        sourceType: 'public_action',
        sourceId: 'hero-button',
    );
}

final class PublicActionProviderUsernameRegistrationField implements RegistrationField
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

        return new RegistrationFieldValue(
            key: $this->key(),
            value: strtolower((string) $validated['provider_username']),
        );
    }
}
