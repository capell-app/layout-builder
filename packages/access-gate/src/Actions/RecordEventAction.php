<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Models\ClaimToken;
use Capell\AccessGate\Models\Event;
use Capell\AccessGate\Models\Grant;
use Capell\AccessGate\Models\Registration;
use Illuminate\Database\Eloquent\Model;

final class RecordEventAction
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        EventType|string $type,
        ?Area $area = null,
        ?Registration $registration = null,
        ?Grant $grant = null,
        ?ClaimToken $claimToken = null,
        ?BrowserToken $browserToken = null,
        ?int $userId = null,
        array $payload = [],
        array $metadata = [],
        ?Model $subject = null,
    ): Event {
        return Event::query()->create([
            'access_area_id' => $area?->getKey() ?? $registration?->access_area_id ?? $grant?->access_area_id ?? $claimToken?->access_area_id ?? $browserToken?->access_area_id,
            'registration_id' => $registration?->getKey(),
            'grant_id' => $grant?->getKey(),
            'claim_token_id' => $claimToken?->getKey(),
            'browser_token_id' => $browserToken?->getKey(),
            'user_id' => $userId,
            'type' => $type instanceof EventType ? $type : $type,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'payload' => $payload,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
