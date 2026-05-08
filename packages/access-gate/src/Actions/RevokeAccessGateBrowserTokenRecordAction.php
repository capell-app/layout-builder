<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\BrowserTokenStatus;
use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Support\AccessGateDatabase;
use Lorisleiva\Actions\Concerns\AsAction;

final class RevokeAccessGateBrowserTokenRecordAction
{
    use AsAction;

    public function __construct(
        private readonly RecordEventAction $recordEvent,
    ) {}

    public function handle(BrowserToken $browserToken): BrowserToken
    {
        return AccessGateDatabase::transaction(function () use ($browserToken): BrowserToken {
            $lockedBrowserToken = BrowserToken::query()
                ->whereKey($browserToken->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBrowserToken->status !== BrowserTokenStatus::Revoked) {
                $lockedBrowserToken->forceFill([
                    'status' => BrowserTokenStatus::Revoked,
                    'revoked_at' => now(),
                ])->save();
            }

            $this->recordEvent->handle(
                type: EventType::BrowserTokenRevoked,
                grant: $lockedBrowserToken->grant,
                browserToken: $lockedBrowserToken,
            );

            return $lockedBrowserToken;
        });
    }
}
