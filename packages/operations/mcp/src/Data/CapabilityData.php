<?php

declare(strict_types=1);

namespace Capell\Mcp\Data;

use Capell\Mcp\Contracts\CapellMcpCapabilityAction;
use Capell\Mcp\Enums\CapabilityRiskEnum;
use Capell\Mcp\Enums\CapabilityServerEnum;
use Spatie\LaravelData\Data;

final class CapabilityData extends Data
{
    /**
     * @param  class-string<CapellMcpCapabilityAction>  $actionClass
     * @param  class-string<Data>|null  $inputDataClass
     * @param  class-string<Data>|null  $outputDataClass
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $description,
        public readonly string $scope,
        public readonly CapabilityServerEnum $server,
        public readonly CapabilityRiskEnum $risk,
        public readonly string $actionClass,
        public readonly ?string $requiredPackage = null,
        public readonly ?string $policyAbility = null,
        public readonly ?string $inputDataClass = null,
        public readonly ?string $outputDataClass = null,
        public readonly bool $supportsPreview = true,
        public readonly bool $requiresConfirmation = true,
        public readonly ?string $auditEvent = null,
    ) {}

    public function needsConfirmation(): bool
    {
        return $this->requiresConfirmation || $this->risk->requiresConfirmation();
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     scope: string,
     *     server: string,
     *     risk: string,
     *     requiredPackage: string|null,
     *     policyAbility: string|null,
     *     inputDataClass: string|null,
     *     outputDataClass: string|null,
     *     supportsPreview: bool,
     *     requiresConfirmation: bool,
     *     auditEvent: string|null
     * }
     */
    public function toPayload(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'scope' => $this->scope,
            'server' => $this->server->value,
            'risk' => $this->risk->value,
            'requiredPackage' => $this->requiredPackage,
            'policyAbility' => $this->policyAbility,
            'inputDataClass' => $this->inputDataClass,
            'outputDataClass' => $this->outputDataClass,
            'supportsPreview' => $this->supportsPreview,
            'requiresConfirmation' => $this->requiresConfirmation,
            'auditEvent' => $this->auditEvent,
        ];
    }
}
