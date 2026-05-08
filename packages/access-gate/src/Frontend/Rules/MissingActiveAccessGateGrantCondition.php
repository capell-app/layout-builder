<?php

declare(strict_types=1);

namespace Capell\AccessGate\Frontend\Rules;

use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Models\Area;
use Capell\Frontend\Contracts\FrontendRuleCondition;
use Capell\Frontend\Data\FrontendRuleContextData;

final class MissingActiveAccessGateGrantCondition implements FrontendRuleCondition
{
    public function __construct(
        private readonly HasActiveAccessGateGrantCondition $hasActiveGrant,
    ) {}

    public function key(): string
    {
        return 'access_gate_missing_active_grant';
    }

    public function evaluate(array $parameters, FrontendRuleContextData $context): bool
    {
        $areaKeys = $this->hasActiveGrant->areaKeys($parameters);

        if (! $this->referencesKnownActiveAreas($areaKeys)) {
            return false;
        }

        return ! $this->hasActiveGrant->evaluate($parameters, $context);
    }

    /**
     * @param  list<string>  $areaKeys
     */
    private function referencesKnownActiveAreas(array $areaKeys): bool
    {
        if ($areaKeys === []) {
            return false;
        }

        $uniqueAreaKeys = array_values(array_unique($areaKeys));

        $areas = Area::query()
            ->whereIn('key', $uniqueAreaKeys)
            ->get(['key', 'status']);

        return $areas->count() === count($uniqueAreaKeys)
            && $areas->contains(fn (Area $area): bool => $area->status === AccessAreaStatus::Active);
    }
}
