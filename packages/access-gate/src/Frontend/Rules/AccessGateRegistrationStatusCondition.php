<?php

declare(strict_types=1);

namespace Capell\AccessGate\Frontend\Rules;

use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\Registration;
use Capell\Frontend\Contracts\FrontendRuleCondition;
use Capell\Frontend\Data\FrontendRuleContextData;
use Illuminate\Support\Str;

final class AccessGateRegistrationStatusCondition implements FrontendRuleCondition
{
    public function key(): string
    {
        return 'access_gate_registration_status';
    }

    public function evaluate(array $parameters, FrontendRuleContextData $context): bool
    {
        $areaKey = $parameters['area'] ?? null;
        $email = $parameters['email'] ?? data_get($context->request->user(), 'email');

        if (! is_string($areaKey) || $areaKey === '' || ! is_string($email) || $email === '') {
            return false;
        }

        $statuses = $parameters['statuses'] ?? $parameters['status'] ?? [];

        if (is_string($statuses)) {
            $statuses = [$statuses];
        }

        if (! is_array($statuses)) {
            return false;
        }

        $area = Area::query()->where('key', $areaKey)->first();

        if (! $area instanceof Area) {
            return false;
        }

        $registration = Registration::query()
            ->where('access_area_id', $area->getKey())
            ->where('email_normalized', Str::lower($email))
            ->latest('requested_at')
            ->first();

        if (! $registration instanceof Registration) {
            return false;
        }

        return collect($statuses)
            ->filter(fn (mixed $status): bool => is_string($status) && $status !== '')
            ->contains($registration->status->value);
    }
}
