<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\AIOrchestrator;

use Capell\AIOrchestrator\Contracts\AIOrchestratorModule;
use Capell\AIOrchestrator\Data\AIOrchestratorCapabilityData;
use Capell\AIOrchestrator\Enums\AIOrchestratorApprovalLevel;

class LayoutBuilderAIOrchestratorModule implements AIOrchestratorModule
{
    public function key(): string
    {
        return 'layout-builder';
    }

    public function label(): string
    {
        return 'LayoutBuilder';
    }

    /**
     * @return array<int, AIOrchestratorCapabilityData>
     */
    public function capabilities(): array
    {
        return [
            new AIOrchestratorCapabilityData(
                key: 'preview-layout-plan',
                label: 'Preview layout plan',
                description: 'Draft a LayoutBuilder layout plan from a natural-language prompt.',
                actionClass: PreviewLayoutBuilderLayoutPlanAction::class,
                approvalLevel: AIOrchestratorApprovalLevel::Draft,
            ),
        ];
    }
}
