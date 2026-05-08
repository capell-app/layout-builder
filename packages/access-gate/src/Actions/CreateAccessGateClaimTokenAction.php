<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Data\IssuedAccessGateTokenData;
use Capell\AccessGate\Enums\ClaimTokenStatus;
use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Models\ClaimToken;
use Capell\AccessGate\Models\Grant;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateAccessGateClaimTokenAction
{
    use AsAction;

    public function __construct(
        private readonly EnsureAccessGateGrantCanIssueTokenAction $ensureTokenIssuableGrant,
        private readonly RecordEventAction $recordEvent,
    ) {}

    public function handle(Grant $grant, ?CarbonInterface $expiresAt = null): IssuedAccessGateTokenData
    {
        $grant = $this->ensureTokenIssuableGrant->handle($grant);
        $plainTextToken = Str::random(64);

        $claimToken = ClaimToken::query()->create([
            'access_area_id' => $grant->access_area_id,
            'registration_id' => $grant->registration_id,
            'grant_id' => $grant->getKey(),
            'token_hash' => hash('sha256', $plainTextToken),
            'status' => ClaimTokenStatus::Active,
            'expires_at' => $expiresAt ?? now()->addDays(7),
            'consumed_at' => null,
            'metadata' => [],
        ]);

        $this->recordEvent->handle(
            type: EventType::ClaimTokenCreated,
            grant: $grant,
            claimToken: $claimToken,
        );

        return new IssuedAccessGateTokenData($plainTextToken, $claimToken);
    }
}
