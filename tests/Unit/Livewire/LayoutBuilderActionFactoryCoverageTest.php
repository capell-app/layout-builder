<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Livewire\Filament\Actions\LayoutBuilderActionFactory;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Filament\Actions\Action;

it('builds every layout builder action with stable names', function (): void {
    $layoutBuilder = new LayoutBuilder;
    $layoutBuilder->layout = Layout::factory()->create();
    $layoutBuilder->containers = [
        'main' => [
            'widgets' => [],
        ],
    ];
    $layoutBuilder->assets = ['main' => []];
    $layoutBuilder->selectedRecords = ['main' => []];

    $factory = new LayoutBuilderActionFactory($layoutBuilder);

    $actions = [
        $factory->saveLayoutAction(),
        $factory->duplicateLayoutAction(),
        $factory->cloneLayoutForPageAction(),
        $factory->undoLayoutMutationAction(),
        $factory->redoLayoutMutationAction(),
        $factory->addContainerAction(),
        $factory->editContainerAction(),
        $factory->removeContainerAction(),
        $factory->moveContainerUpAction(),
        $factory->moveContainerDownAction(),
        $factory->duplicateContainerAction(),
        $factory->editLayoutBlockAction(),
        $factory->addBlockAction(),
        $factory->editBlockAction(),
        $factory->duplicateBlockAction(),
        $factory->moveBlockUpAction(),
        $factory->moveBlockDownAction(),
        $factory->moveBlockToContainerAction(),
        $factory->removeBlockAction(),
        $factory->selectAssetAction(),
        $factory->addAssetAction(),
        $factory->editBlockAssetAction(),
        $factory->moveAssetUpAction(),
        $factory->moveAssetDownAction(),
        $factory->removeAssetsAction(),
        $factory->changeLayoutAction(),
        $factory->togglePageAssetsAction(),
    ];

    expect($actions)
        ->each->toBeInstanceOf(Action::class)
        ->and(array_map(
            static fn (Action $action): string => $action->getName(),
            $actions,
        ))->toBe([
            'saveLayout',
            'duplicateLayout',
            'cloneLayoutForPage',
            'undoLayoutMutation',
            'redoLayoutMutation',
            'addContainer',
            'editContainer',
            'removeContainer',
            'moveContainerUp',
            'moveContainerDown',
            'duplicateContainer',
            'editLayoutBlock',
            'addBlock',
            'editBlock',
            'duplicateBlock',
            'moveBlockUp',
            'moveBlockDown',
            'moveBlockToContainer',
            'removeBlock',
            'selectAsset',
            'addAsset',
            'editBlockAsset',
            'moveAssetUp',
            'moveAssetDown',
            'removeAssets',
            'changeLayout',
            'togglePageAssets',
        ]);
});
