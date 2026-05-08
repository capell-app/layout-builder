<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\BrowserTokenStatus;
use Capell\AccessGate\Enums\ClaimTokenStatus;
use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\RegistrationStatus;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Capell\AccessGate\Notifications\AccessExpiredNotification;
use Capell\AccessGate\Support\AccessGateDatabase;
use Illuminate\Support\Facades\Notification;
use LogicException;
use Lorisleiva\Actions\Concerns\AsAction;

final class ExpireRegistrationAction
{
    use AsAction;

    public function __construct(
        private readonly RecordEventAction $recordEvent,
    ) {}

    public function handle(Registration $registration, ?int $expiredByUserId = null): Registration
    {
        return AccessGateDatabase::transaction(function () use ($registration, $expiredByUserId): Registration {
            $lockedRegistration = Registration::query()
                ->whereKey($registration->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRegistration->status === RegistrationStatus::Expired) {
                return $lockedRegistration;
            }

            throw_if($lockedRegistration->status === RegistrationStatus::Claimed, LogicException::class, 'Claimed access gate registrations cannot be expired.');

            $lockedRegistration->forceFill([
                'status' => RegistrationStatus::Expired,
                'expired_at' => now(),
            ])->save();

            $lockedRegistration->claimTokens()
                ->where('status', ClaimTokenStatus::Active->value)
                ->update([
                    'status' => ClaimTokenStatus::Expired->value,
                ]);

            $lockedRegistration->grants()
                ->where('status', GrantStatus::Active->value)
                ->update([
                    'status' => GrantStatus::Expired->value,
                    'expires_at' => now(),
                ]);

            $lockedRegistration->grants()
                ->with('browserTokens')
                ->get()
                ->each(function (Grant $grant): void {
                    $grant->browserTokens()
                        ->where('status', BrowserTokenStatus::Active->value)
                        ->update([
                            'status' => BrowserTokenStatus::Expired->value,
                            'expires_at' => now(),
                        ]);
                });

            $this->recordEvent->handle(
                type: EventType::RegistrationExpired,
                registration: $lockedRegistration,
                userId: $expiredByUserId,
                payload: [
                    'expired_by_user_id' => $expiredByUserId,
                ],
            );

            if ($lockedRegistration->email !== '') {
                Notification::route('mail', $lockedRegistration->email)
                    ->notify(new AccessExpiredNotification($lockedRegistration->area()->firstOrFail()));
            }

            return $lockedRegistration;
        });
    }
}
