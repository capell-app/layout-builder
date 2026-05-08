<?php

declare(strict_types=1);

namespace Capell\AccessGate\Frontend\Rules;

use Capell\AccessGate\Actions\ResolveAccessGateAccessAction;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Models\Grant;
use Capell\Frontend\Contracts\FrontendRuleCondition;
use Capell\Frontend\Data\FrontendRuleContextData;

final class HasActiveAccessGateGrantCondition implements FrontendRuleCondition
{
    public function __construct(
        private readonly ResolveAccessGateAccessAction $resolveAccess,
    ) {}

    public function key(): string
    {
        return 'access_gate_has_active_grant';
    }

    public function evaluate(array $parameters, FrontendRuleContextData $context): bool
    {
        $areaKeys = $this->areaKeys($parameters);

        if ($areaKeys === []) {
            return false;
        }

        $result = $this->resolveAccess->handle($context->request, $areaKeys);

        return $result->allowed
            && $result->grant instanceof Grant
            && $result->area?->status === AccessAreaStatus::Active;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return list<string>
     */
    public function areaKeys(array $parameters): array
    {
        $areaKeys = $parameters['areas'] ?? $parameters['area'] ?? [];

        if (is_string($areaKeys)) {
            $areaKeys = [$areaKeys];
        }

        if (! is_array($areaKeys)) {
            return [];
        }

        return collect($areaKeys)
            ->filter(fn (mixed $areaKey): bool => is_string($areaKey) && $areaKey !== '')
            ->values()
            ->all();
    }
}
