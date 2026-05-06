<?php

declare(strict_types=1);

use Capell\AIOrchestrator\Actions\RegisterAIOrchestratorModuleAction;
use Capell\AIOrchestrator\Actions\RunAIOrchestratorCapabilityAction;
use Capell\AIOrchestrator\Contracts\AIOrchestratorModule;
use Capell\AIOrchestrator\Data\AIOrchestratorCapabilityData;
use Capell\AIOrchestrator\Data\AIOrchestratorRunData;

it('runs a capability through the registered package action', function (): void {
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
                    actionClass: AIOrchestratorRunActionFixture::class,
                ),
            ];
        }
    });

    $result = RunAIOrchestratorCapabilityAction::run(new AIOrchestratorRunData(
        moduleKey: 'layout-builder',
        capabilityKey: 'preview-layout-plan',
        prompt: 'Create a sidebar layout',
        context: ['page' => 'home'],
    ));

    expect($result)->toBe([
        'prompt' => 'Create a sidebar layout',
        'page' => 'home',
    ]);
});

class AIOrchestratorRunActionFixture
{
    /**
     * @return array<string, string>
     */
    public static function run(AIOrchestratorRunData $run): array
    {
        return [
            'prompt' => $run->prompt,
            'page' => (string) $run->context['page'],
        ];
    }
}
