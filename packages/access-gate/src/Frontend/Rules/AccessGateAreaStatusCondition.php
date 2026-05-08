<?php

declare(strict_types=1);

namespace Capell\AccessGate\Frontend\Rules;

use Capell\AccessGate\Models\Area;
use Capell\Frontend\Contracts\FrontendRuleCondition;
use Capell\Frontend\Data\FrontendRuleContextData;

final class AccessGateAreaStatusCondition implements FrontendRuleCondition
{
    public function key(): string
    {
        return 'access_gate_area_status';
    }

    public function evaluate(array $parameters, FrontendRuleContextData $context): bool
    {
        $areaKey = $parameters['area'] ?? null;

        if (! is_string($areaKey) || $areaKey === '') {
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

        return collect($statuses)
            ->filter(fn (mixed $status): bool => is_string($status) && $status !== '')
            ->contains($area->status->value);
    }
}
