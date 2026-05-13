<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Contracts\RegistrationField;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\ApprovalStrategy;
use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Enums\RegistrationPolicy;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Notifications\AccessRequestReceivedNotification;
use Capell\AccessGate\Support\AccessGateDatabase;
use Capell\AccessGate\Support\RegistrationFieldRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateRegistrationAction
{
    use AsAction;

    public function __construct(
        private readonly RegistrationFieldRegistry $fields,
        private readonly RecordEventAction $recordEvent,
        private readonly ApproveRegistrationAction $approveRegistration,
        private readonly ResendAccessGateClaimTokenAction $resendClaimToken,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function handle(Area|string $area, array $input): Registration
    {
        $area = $this->resolveArea($area);
        $validated = Validator::make($input, [
            'email' => ['required', 'email:rfc', 'max:255'],
            'requested_url' => ['nullable', 'url', 'max:2048'],
            'requested_host' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ])->validate();

        $email = (string) $validated['email'];
        $emailNormalized = Str::lower($email);
        $fieldValues = $this->validatedFieldValues($input);

        return AccessGateDatabase::transaction(function () use ($area, $email, $emailNormalized, $fieldValues, $validated): Registration {
            $lockedArea = Area::query()
                ->whereKey($area->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertAreaAcceptsPublicRegistrations($lockedArea);

            $singleRegistrationKey = $this->singleRegistrationKey($lockedArea, $emailNormalized);
            $existingRegistration = $this->existingSingleRegistration($singleRegistrationKey);

            if ($existingRegistration instanceof Registration) {
                if ($existingRegistration->status === RegistrationStatus::Approved || $existingRegistration->status === RegistrationStatus::Claimed) {
                    $this->resendClaimToken->handle($existingRegistration);
                }

                return $existingRegistration;
            }

            $parsedRequestedHost = parse_url((string) ($validated['requested_url'] ?? ''), PHP_URL_HOST);

            $attributes = [
                'access_area_id' => $lockedArea->getKey(),
                'email' => $email,
                'email_normalized' => $emailNormalized,
                'single_registration_key' => $singleRegistrationKey,
                'user_id' => $validated['user_id'] ?? null,
                'status' => RegistrationStatus::Pending,
                'requested_url' => $validated['requested_url'] ?? null,
                'requested_host' => $validated['requested_host'] ?? (is_string($parsedRequestedHost) ? $parsedRequestedHost : null),
                'position' => $this->nextPosition($lockedArea),
                'field_values' => $fieldValues,
                'metadata' => Arr::wrap($validated['metadata'] ?? []),
                'requested_at' => now(),
            ];

            $registration = $this->persistRegistration($singleRegistrationKey, $attributes);

            $this->recordEvent->handle(
                type: EventType::RegistrationCreated,
                registration: $registration,
                userId: $registration->user_id,
                payload: [
                    'field_keys' => array_keys($fieldValues),
                ],
            );

            if ($registration->status === RegistrationStatus::Pending && $this->shouldApproveAutomatically($lockedArea)) {
                return $this->approveRegistration->handle($registration);
            }

            if ($registration->status === RegistrationStatus::Approved || $registration->status === RegistrationStatus::Claimed) {
                $this->resendClaimToken->handle($registration);
            } else {
                Notification::route('mail', $registration->email)
                    ->notify(new AccessRequestReceivedNotification($lockedArea));
            }

            return $registration;
        });
    }

    private function existingSingleRegistration(?string $singleRegistrationKey): ?Registration
    {
        if ($singleRegistrationKey === null) {
            return null;
        }

        return Registration::query()
            ->where('single_registration_key', $singleRegistrationKey)
            ->lockForUpdate()
            ->first();
    }

    private function resolveArea(Area|string $area): Area
    {
        if ($area instanceof Area) {
            return $area;
        }

        return Area::query()->where('key', $area)->firstOrFail();
    }

    /**
     * @throws ValidationException
     */
    private function assertAreaAcceptsPublicRegistrations(Area $area): void
    {
        if ($area->status === AccessAreaStatus::Closed || $area->approval_strategy === ApprovalStrategy::InviteOnly) {
            throw ValidationException::withMessages([
                'email' => __('capell-access-gate::public.request_unavailable'),
            ]);
        }

        if ($area->status === AccessAreaStatus::Paused) {
            return;
        }

        if (AreaIsCurrentlyGatingAction::run($area) === true) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('capell-access-gate::public.request_unavailable'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function persistRegistration(?string $singleRegistrationKey, array $attributes): Registration
    {
        if ($singleRegistrationKey === null) {
            return Registration::query()->create($attributes);
        }

        $registration = Registration::query()
            ->where('single_registration_key', $singleRegistrationKey)
            ->lockForUpdate()
            ->first();

        if ($registration === null) {
            try {
                return Registration::query()->create($attributes);
            } catch (QueryException) {
                $registration = Registration::query()
                    ->where('single_registration_key', $singleRegistrationKey)
                    ->lockForUpdate()
                    ->firstOrFail();
            }
        }

        $registration->forceFill(Arr::except($attributes, [
            'status',
            'position',
            'requested_at',
            'approved_at',
            'rejected_at',
            'claimed_at',
            'expired_at',
        ]))->save();

        return $registration;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array{value: mixed, metadata: array<string, mixed>}>
     */
    private function validatedFieldValues(array $input): array
    {
        return collect($this->fields->all())
            ->mapWithKeys(function (RegistrationField $field) use ($input): array {
                $value = $field->validate($input);

                return [$value->key => $value->toArray()];
            })
            ->all();
    }

    private function singleRegistrationKey(Area $area, string $emailNormalized): ?string
    {
        if ($area->registration_policy !== RegistrationPolicy::SinglePerEmail) {
            return null;
        }

        return hash('sha256', $area->getKey() . '|' . $emailNormalized);
    }

    private function nextPosition(Area $area): int
    {
        return ((int) $area->registrations()->max('position')) + 1;
    }

    private function shouldApproveAutomatically(Area $area): bool
    {
        if ($area->approval_strategy === ApprovalStrategy::AutoApprove) {
            return true;
        }

        if ($area->approval_strategy !== ApprovalStrategy::FirstNAutoApprove) {
            return false;
        }

        return is_int($area->approval_limit)
            && $area->approval_limit > 0
            && $area->registrations()->whereNotNull('approved_at')->count() < $area->approval_limit;
    }
}
