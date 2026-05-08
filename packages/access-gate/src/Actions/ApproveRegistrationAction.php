<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Enums\GrantSubjectType;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Events\RegistrationApproved;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Support\AccessGateDatabase;
use Carbon\CarbonInterface;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

final class ApproveRegistrationAction
{
    use AsAction;

    public function __construct(
        private readonly CreateAccessGateGrantAction $createGrant,
        private readonly SendAccessGateApprovedNotificationAction $sendApprovedNotification,
        private readonly RecordEventAction $recordEvent,
    ) {}

    public function handle(Registration $registration, ?int $approvedByUserId = null): Registration
    {
        return AccessGateDatabase::transaction(function () use ($registration, $approvedByUserId): Registration {
            $lockedRegistration = Registration::query()
                ->whereKey($registration->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRegistration->status === RegistrationStatus::Approved) {
                return $lockedRegistration;
            }

            if ($lockedRegistration->status !== RegistrationStatus::Pending) {
                throw new LogicException('Only pending access gate registrations can be approved.');
            }

            $lockedRegistration->forceFill([
                'status' => RegistrationStatus::Approved,
                'approved_at' => now(),
                'rejected_at' => null,
            ])->save();

            $grant = $this->grantFor($lockedRegistration);
            $this->sendApprovedNotification->handle($lockedRegistration, $grant);

            $this->recordEvent->handle(
                type: EventType::RegistrationApproved,
                registration: $lockedRegistration,
                grant: $grant,
                userId: $approvedByUserId,
                payload: [
                    'approved_by_user_id' => $approvedByUserId,
                ],
            );

            RegistrationApproved::dispatch($lockedRegistration->refresh());

            return $lockedRegistration;
        });
    }

    private function grantFor(Registration $registration): Grant
    {
        $subjectType = $registration->user_id === null
            ? GrantSubjectType::Email
            : GrantSubjectType::User;

        return $this->createGrant->handle(
            area: $registration->area()->firstOrFail(),
            subjectType: $subjectType,
            registration: $registration,
            userId: $registration->user_id,
            email: $registration->email,
            expiresAt: $this->expiresAt($registration),
        );
    }

    private function expiresAt(Registration $registration): ?CarbonInterface
    {
        $duration = $registration->area?->grant_duration_days;

        return is_int($duration) && $duration > 0 ? now()->addDays($duration) : null;
    }
}
