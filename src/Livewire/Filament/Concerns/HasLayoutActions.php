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

    public function editLayoutElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editLayoutElementAction();
    }

    public function addElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addElementAction();
    }

    public function editElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editElementAction();
    }

    public function duplicateElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->duplicateElementAction();
    }

    public function moveElementUpAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveElementUpAction();
    }

    public function moveElementDownAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveElementDownAction();
    }

    public function moveElementToContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveElementToContainerAction();
    }

    public function removeElementAction(): Action
    {
        return $this->layoutBuilderActionFactory()->removeElementAction();
    }

    public function selectAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->selectAssetAction();
    }

    public function addAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addAssetAction();
    }

    public function editElementAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editElementAssetAction();
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
