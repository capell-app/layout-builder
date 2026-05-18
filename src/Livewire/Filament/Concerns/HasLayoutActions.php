<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\LayoutBuilder\Livewire\Filament\Actions\LayoutBuilderActionFactory;
use Filament\Actions\Action;
use Filament\Schemas\Schema;

trait HasLayoutActions
{
    public function saveLayoutAction(): Action
    {
        return $this->layoutBuilderActionFactory()->saveLayoutAction();
    }

    public function duplicateLayoutAction(): Action
    {
        return $this->layoutBuilderActionFactory()->duplicateLayoutAction();
    }

    public function cloneLayoutForPageAction(): Action
    {
        return $this->layoutBuilderActionFactory()->cloneLayoutForPageAction();
    }

    public function undoLayoutMutationAction(): Action
    {
        return $this->layoutBuilderActionFactory()->undoLayoutMutationAction();
    }

    public function redoLayoutMutationAction(): Action
    {
        return $this->layoutBuilderActionFactory()->redoLayoutMutationAction();
    }

    public function addContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addContainerAction();
    }

    public function editContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editContainerAction();
    }

    public function removeContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->removeContainerAction();
    }

    public function moveContainerUpAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveContainerUpAction();
    }

    public function moveContainerDownAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveContainerDownAction();
    }

    public function duplicateContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->duplicateContainerAction();
    }

    public function editLayoutBlockAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editLayoutBlockAction();
    }

    public function addBlockAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addBlockAction();
    }

    public function editBlockAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editBlockAction();
    }

    public function duplicateBlockAction(): Action
    {
        return $this->layoutBuilderActionFactory()->duplicateBlockAction();
    }

    public function moveBlockUpAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveBlockUpAction();
    }

    public function moveBlockDownAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveBlockDownAction();
    }

    public function moveBlockToContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveBlockToContainerAction();
    }

    public function removeBlockAction(): Action
    {
        return $this->layoutBuilderActionFactory()->removeBlockAction();
    }

    public function selectAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->selectAssetAction();
    }

    public function addAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addAssetAction();
    }

    public function editBlockAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editBlockAssetAction();
    }

    public function moveAssetUpAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveAssetUpAction();
    }

    public function moveAssetDownAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveAssetDownAction();
    }

    public function removeAssetsAction(): Action
    {
        return $this->layoutBuilderActionFactory()->removeAssetsAction();
    }

    public function changeLayoutAction(): Action
    {
        return $this->layoutBuilderActionFactory()->changeLayoutAction();
    }

    public function togglePageAssetsAction(): Action
    {
        return $this->layoutBuilderActionFactory()->togglePageAssetsAction();
    }

    public function getLayoutBuilderMountedActionSchema(): ?Schema
    {
        return $this->getMountedActionSchema();
    }

    private function layoutBuilderActionFactory(): LayoutBuilderActionFactory
    {
        $livewire = $this;

        return new LayoutBuilderActionFactory($livewire);
    }
}
