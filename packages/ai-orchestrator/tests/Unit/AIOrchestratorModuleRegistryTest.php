<?php

declare(strict_types=1);

use Capell\AIOrchestrator\Actions\ListAIOrchestratorCapabilitiesAction;
use Capell\AIOrchestrator\Actions\RegisterAIOrchestratorModuleAction;
use Capell\AIOrchestrator\Contracts\AIOrchestratorModule;
use Capell\AIOrchestrator\Data\AIOrchestratorCapabilityData;
use Capell\AIOrchestrator\Enums\AIOrchestratorApprovalLevel;

it('registers shallow ai-orchestrator modules and lists their capabilities', function (): void {
    RegisterAIOrchestratorModuleAction::run(new class implements AIOrchestratorModule
    {
        public function key(): string
        {
            return 'layout-builder';
        }

        public function label(): string
        {
            return 'LayoutBuilder';
        }

        public function capabilities(): array
        {
            return [
                new AIOrchestratorCapabilityData(
                    key: 'preview-layout-plan',
                    label: 'Preview layout plan',
                    description: 'Create a draft LayoutBuilder layout plan.',
                    actionClass: RegisterAIOrchestratorModuleAction::class,
                    approvalLevel: AIOrchestratorApprovalLevel::Draft,
                ),
            ];
        }
    });

    $capabilities = ListAIOrchestratorCapabilitiesAction::run();

    expect($capabilities)->toHaveCount(1)
        ->and($capabilities[0]->key)->toBe('preview-layout-plan')
        ->and($capabilities[0]->approvalLevel)->toBe(AIOrchestratorApprovalLevel::Draft);
});
