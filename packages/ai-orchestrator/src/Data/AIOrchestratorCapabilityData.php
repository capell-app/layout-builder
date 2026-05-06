<?php

declare(strict_types=1);

namespace Capell\AIOrchestrator\Data;

use Capell\AIOrchestrator\Enums\AIOrchestratorApprovalLevel;
use Spatie\LaravelData\Data;

class AIOrchestratorCapabilityData extends Data
{
    /**
     * @param  class-string  $actionClass
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string $actionClass,
        public AIOrchestratorApprovalLevel $approvalLevel = AIOrchestratorApprovalLevel::Draft,
    ) {}
}
