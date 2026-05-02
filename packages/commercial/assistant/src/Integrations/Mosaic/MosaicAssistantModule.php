<?php

declare(strict_types=1);

namespace Capell\Assistant\Integrations\Mosaic;

use Capell\Assistant\Contracts\AssistantModule;
use Capell\Assistant\Data\AssistantCapabilityData;
use Capell\Assistant\Enums\AssistantApprovalLevel;

class MosaicAssistantModule implements AssistantModule
{
    public function key(): string
    {
        return 'mosaic';
    }

    public function label(): string
    {
        return 'Mosaic';
    }

    /**
     * @return array<int, AssistantCapabilityData>
     */
    public function capabilities(): array
    {
        return [
            new AssistantCapabilityData(
                key: 'preview-layout-plan',
                label: 'Preview layout plan',
                description: 'Draft a Mosaic layout plan from a natural-language prompt.',
                actionClass: PreviewMosaicLayoutPlanAction::class,
                approvalLevel: AssistantApprovalLevel::Draft,
            ),
        ];
    }
}
