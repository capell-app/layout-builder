<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Support\AccessGateDatabase;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

final class RejectRegistrationAction
{
    use AsAction;

    public function __construct(
        private readonly RecordEventAction $recordEvent,
    ) {}

    public function handle(Registration $registration, ?int $rejectedByUserId = null): Registration
    {
        return AccessGateDatabase::transaction(function () use ($registration, $rejectedByUserId): Registration {
            $lockedRegistration = Registration::query()
                ->whereKey($registration->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRegistration->status !== RegistrationStatus::Pending) {
                throw new LogicException('Only pending access gate registrations can be rejected.');
            }

            $lockedRegistration->forceFill([
                'status' => RegistrationStatus::Rejected,
                'rejected_at' => now(),
            ])->save();

            $this->recordEvent->handle(
                type: EventType::RegistrationRejected,
                registration: $lockedRegistration,
                userId: $rejectedByUserId,
                payload: [
                    'rejected_by_user_id' => $rejectedByUserId,
                ],
            );

            return $lockedRegistration;
        });
    }
}
