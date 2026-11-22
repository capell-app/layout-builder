<?php

declare(strict_types=1);

use Capell\AIOrchestrator\Data\AIOrchestratorRunData;
use Capell\AIOrchestrator\Enums\AIOrchestratorApprovalLevel;
use Capell\AIOrchestrator\Support\AIOrchestratorModuleRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Bridges\AiOrchestrator\LayoutBuilderAIOrchestratorModule;
use Capell\LayoutBuilder\Bridges\AiOrchestrator\PreviewLayoutBuilderLayoutPlanAction;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Foundation\Application;

it('offers layout planning to AI Orchestrator only when that package is available', function (bool $aiOrchestratorAvailable): void {
    InstalledRuntimeState::run(function () use ($aiOrchestratorAvailable): void {
        $application = InstalledRuntimeTestCase::boot(
            InstalledRuntimeParity::manifest('layout-builder'),
            true,
            function (Application $application) use ($aiOrchestratorAvailable): void {
                if ($aiOrchestratorAvailable) {
                    CapellCore::registerPackage('capell-app/ai-orchestrator', path: (string) realpath(__DIR__ . '/../../../../ai-orchestrator'));
                    CapellCore::forcePackageInstalled('capell-app/ai-orchestrator');
                }
            },
        );

        $modules = $application->make(AIOrchestratorModuleRegistry::class)->modules();

        if ($aiOrchestratorAvailable) {
            expect($modules)->toHaveKey('layout-builder')
                ->and($modules['layout-builder'])->toBeInstanceOf(LayoutBuilderAIOrchestratorModule::class);

            return;
        }

        expect($modules)->not->toHaveKey('layout-builder');
    });
})->with(['AI Orchestrator installed' => true, 'AI Orchestrator absent' => false]);

it('describes the layout planning capability it offers', function (): void {
    $module = new LayoutBuilderAIOrchestratorModule;
    $capabilities = $module->capabilities();

    expect($module->key())->toBe('layout-builder')
        ->and($capabilities)->toHaveCount(1)
        ->and($capabilities[0]->key)->toBe('preview-layout-plan')
        ->and($capabilities[0]->actionClass)->toBe(PreviewLayoutBuilderLayoutPlanAction::class)
        ->and($capabilities[0]->approvalLevel)->toBe(AIOrchestratorApprovalLevel::Draft);
});

it('plans a layout from an AI Orchestrator run', function (): void {
    $result = PreviewLayoutBuilderLayoutPlanAction::run(new AIOrchestratorRunData(
        moduleKey: 'layout-builder',
        capabilityKey: 'preview-layout-plan',
        prompt: 'Draft a landing page',
        context: ['page' => 'home'],
    ));

    expect($result->plan->prompt)->toBe('Draft a landing page');
});
