<?php

declare(strict_types=1);

use Capell\Assistant\Actions\RegisterAssistantModuleAction;
use Capell\Assistant\Actions\RunAssistantCapabilityAction;
use Capell\Assistant\Contracts\AssistantModule;
use Capell\Assistant\Data\AssistantCapabilityData;
use Capell\Assistant\Data\AssistantRunData;

it('runs a capability through the registered package action', function (): void {
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
                    actionClass: AssistantRunActionFixture::class,
                ),
            ];
        }
    });

    $result = RunAssistantCapabilityAction::run(new AssistantRunData(
        moduleKey: 'mosaic',
        capabilityKey: 'preview-layout-plan',
        prompt: 'Create a sidebar layout',
        context: ['page' => 'home'],
    ));

    expect($result)->toBe([
        'prompt' => 'Create a sidebar layout',
        'page' => 'home',
    ]);
});

class AssistantRunActionFixture
{
    /**
     * @return array<string, string>
     */
    public static function run(AssistantRunData $run): array
    {
        return [
            'prompt' => $run->prompt,
            'page' => (string) $run->context['page'],
        ];
    }
}
