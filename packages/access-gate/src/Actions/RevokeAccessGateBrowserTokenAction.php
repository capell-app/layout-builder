<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Enums\BrowserTokenStatus;
use Capell\AccessGate\Enums\EventType;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Lorisleiva\Actions\Concerns\AsAction;

final class RevokeAccessGateBrowserTokenAction
{
    use AsAction;

    public function __construct(
        private readonly RecordEventAction $recordEvent,
    ) {}

    public function handle(Area|string $area, ?string $plainTextToken): ?BrowserToken
    {
        if ($plainTextToken === null || $plainTextToken === '') {
            return null;
        }

        $area = $this->resolveArea($area);

        $browserToken = BrowserToken::query()
            ->where('access_area_id', $area->getKey())
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->where('status', BrowserTokenStatus::Active->value)
            ->first();

        if ($browserToken === null) {
            return null;
        }

        $browserToken->forceFill([
            'status' => BrowserTokenStatus::Revoked,
            'revoked_at' => now(),
        ])->save();

        $this->recordEvent->handle(
            type: EventType::BrowserTokenRevoked,
            area: $area,
            grant: $browserToken->grant,
            browserToken: $browserToken,
        );

        return $browserToken;
    }

    private function resolveArea(Area|string $area): Area
    {
        if ($area instanceof Area) {
            return $area;
        }

        return Area::query()->where('key', $area)->firstOrFail();
    }
}
