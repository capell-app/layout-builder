<?php

declare(strict_types=1);

namespace Capell\Assistant\Data;

use Capell\Assistant\Enums\AssistantApprovalLevel;
use Spatie\LaravelData\Data;

class AssistantCapabilityData extends Data
{
    /**
     * @param  class-string  $actionClass
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string $actionClass,
        public AssistantApprovalLevel $approvalLevel = AssistantApprovalLevel::Draft,
    ) {}
}
