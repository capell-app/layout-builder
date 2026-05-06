<?php

declare(strict_types=1);

use Capell\AIOrchestrator\Data\AIOrchestratorRunData;
use Capell\AIOrchestrator\Enums\AIOrchestratorApprovalLevel;
use Capell\LayoutBuilder\Actions\ListLayoutPresetsAction;
use Capell\LayoutBuilder\AIOrchestrator\LayoutBuilderAIOrchestratorModule;
use Capell\LayoutBuilder\AIOrchestrator\PreviewLayoutBuilderLayoutPlanAction;

it('exposes a shallow ai-orchestrator capability for LayoutBuilder layout planning', function (): void {
    $module = new LayoutBuilderAIOrchestratorModule;
    $capabilities = $module->capabilities();

    expect($module->key())->toBe('layout-builder')
        ->and($capabilities)->toHaveCount(1)
        ->and($capabilities[0]->key)->toBe('preview-layout-plan')
        ->and($capabilities[0]->approvalLevel)->toBe(AIOrchestratorApprovalLevel::Draft);
});

it('previews a reusable LayoutBuilder sidebar layout plan from an ai-orchestrator run', function (): void {
    $presets = ListLayoutPresetsAction::run();

    $result = PreviewLayoutBuilderLayoutPlanAction::run(new AIOrchestratorRunData(
        moduleKey: 'layout-builder',
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
