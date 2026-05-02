<?php

declare(strict_types=1);

use Capell\Assistant\Data\AssistantRunData;
use Capell\Assistant\Enums\AssistantApprovalLevel;
use Capell\Assistant\Integrations\Mosaic\MosaicAssistantModule;
use Capell\Assistant\Integrations\Mosaic\PreviewMosaicLayoutPlanAction;
use Capell\Mosaic\Actions\ListLayoutPresetsAction;

it('exposes a shallow assistant capability for Mosaic layout planning', function (): void {
    $module = new MosaicAssistantModule;
    $capabilities = $module->capabilities();

    expect($module->key())->toBe('mosaic')
        ->and($capabilities)->toHaveCount(1)
        ->and($capabilities[0]->key)->toBe('preview-layout-plan')
        ->and($capabilities[0]->approvalLevel)->toBe(AssistantApprovalLevel::Draft);
});

it('previews a reusable Mosaic sidebar layout plan from an assistant run', function (): void {
    $presets = ListLayoutPresetsAction::run();

    $result = PreviewMosaicLayoutPlanAction::run(new AssistantRunData(
        moduleKey: 'mosaic',
        capabilityKey: 'preview-layout-plan',
        prompt: 'Create me a sidebar layout with a hero section and footer sign-up form.',
    ));

    expect($presets)->not->toBeEmpty()
        ->and($result->status)->toBe('draft')
        ->and($result->requiresApproval)->toBeTrue()
        ->and($result->plan->presetKey)->toBe('sidebar-main-footer')
        ->and($result->plan->containers)->toBe(['sidebar', 'main', 'footer'])
        ->and($result->plan->sections)->toBe(['hero', 'content', 'signup-footer']);
});
