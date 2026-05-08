<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\GrantSubjectType;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Events\RegistrationApproved;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Carbon\CarbonInterface;

final class ApproveRegistrationAction
{
    public function __construct(
        private readonly RecordEventAction $recordEvent,
    ) {}

    public function handle(Registration $registration, ?int $approvedByUserId = null): Registration
    {
        if ($registration->status === RegistrationStatus::Approved) {
            return $registration;
        }

        $registration->forceFill([
            'status' => RegistrationStatus::Approved,
            'approved_at' => now(),
            'rejected_at' => null,
        ])->save();

        $grant = $this->grantFor($registration);

        $this->recordEvent->handle(
            type: EventType::RegistrationApproved,
            registration: $registration,
            grant: $grant,
            userId: $approvedByUserId,
            payload: [
                'approved_by_user_id' => $approvedByUserId,
            ],
        );

        RegistrationApproved::dispatch($registration->refresh());

        return $registration;
    }

    private function grantFor(Registration $registration): Grant
    {
        $subjectType = $registration->user_id === null
            ? GrantSubjectType::Email
            : GrantSubjectType::User;

        $subjectId = $registration->user_id === null
            ? $registration->email_normalized
            : (string) $registration->user_id;

        return Grant::query()->firstOrCreate([
            'access_area_id' => $registration->access_area_id,
            'registration_id' => $registration->getKey(),
        ], [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'user_id' => $registration->user_id,
            'email' => $registration->email,
            'status' => GrantStatus::Active,
            'starts_at' => now(),
            'expires_at' => $this->expiresAt($registration),
            'discount_label' => $registration->area?->discount_label,
            'discount_code' => $registration->area?->discount_code,
            'discount_expires_at' => $registration->area?->discount_expires_at,
            'discount_metadata' => $registration->area?->discount_metadata ?? [],
            'metadata' => [],
        ]);
    }

    private function expiresAt(Registration $registration): ?CarbonInterface
    {
        $duration = $registration->area?->grant_duration_days;

        return is_int($duration) && $duration > 0 ? now()->addDays($duration) : null;
    }
}
