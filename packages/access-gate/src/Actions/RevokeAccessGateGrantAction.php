<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\BrowserTokenStatus;
use Capell\AccessGate\Enums\ClaimTokenStatus;
use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Notifications\AccessRevokedNotification;
use Capell\AccessGate\Support\AccessGateDatabase;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;

final class RevokeAccessGateGrantAction
{
    use AsAction;

    public function __construct(
        private readonly RecordEventAction $recordEvent,
    ) {}

    public function handle(Grant $grant, ?int $revokedByUserId = null): Grant
    {
        return AccessGateDatabase::transaction(function () use ($grant, $revokedByUserId): Grant {
            $lockedGrant = Grant::query()
                ->whereKey($grant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $shouldNotify = $lockedGrant->status !== GrantStatus::Revoked;

            if ($lockedGrant->status !== GrantStatus::Revoked) {
                $lockedGrant->forceFill([
                    'status' => GrantStatus::Revoked,
                    'revoked_at' => now(),
                ])->save();
            }

            $lockedGrant->browserTokens()
                ->where('status', BrowserTokenStatus::Active->value)
                ->update([
                    'status' => BrowserTokenStatus::Revoked->value,
                    'revoked_at' => now(),
                ]);

            $lockedGrant->claimTokens()
                ->where('status', ClaimTokenStatus::Active->value)
                ->update([
                    'status' => ClaimTokenStatus::Revoked->value,
                ]);

            $this->recordEvent->handle(
                type: EventType::GrantRevoked,
                grant: $lockedGrant,
                userId: $revokedByUserId,
                payload: [
                    'revoked_by_user_id' => $revokedByUserId,
                ],
            );

            if ($shouldNotify && is_string($lockedGrant->email) && $lockedGrant->email !== '') {
                Notification::route('mail', $lockedGrant->email)
                    ->notify(new AccessRevokedNotification($lockedGrant->area()->firstOrFail()));
            }

            return $lockedGrant;
        });
    }
}
