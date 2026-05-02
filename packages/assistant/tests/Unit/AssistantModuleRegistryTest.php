<?php

declare(strict_types=1);

use Capell\Assistant\Actions\ListAssistantCapabilitiesAction;
use Capell\Assistant\Actions\RegisterAssistantModuleAction;
use Capell\Assistant\Contracts\AssistantModule;
use Capell\Assistant\Data\AssistantCapabilityData;
use Capell\Assistant\Enums\AssistantApprovalLevel;

it('registers shallow assistant modules and lists their capabilities', function (): void {
    RegisterAssistantModuleAction::run(new class implements AssistantModule
    {
        public function key(): string
        {
            return 'mosaic';
        }

        public function label(): string
        {
            return 'Mosaic';
        }

        public function capabilities(): array
        {
            return [
                new AssistantCapabilityData(
                    key: 'preview-layout-plan',
                    label: 'Preview layout plan',
                    description: 'Create a draft Mosaic layout plan.',
                    actionClass: RegisterAssistantModuleAction::class,
                    approvalLevel: AssistantApprovalLevel::Draft,
                ),
            ];
        }
    });

    $capabilities = ListAssistantCapabilitiesAction::run();

    expect($capabilities)->toHaveCount(1)
        ->and($capabilities[0]->key)->toBe('preview-layout-plan')
        ->and($capabilities[0]->approvalLevel)->toBe(AssistantApprovalLevel::Draft);
});
