<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Livewire\Filament\Concerns;

use Capell\LayoutBuilder\Livewire\Filament\Support\LayoutBuilderActionFactory;
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

    public function editLinkedContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editLinkedContainerAction();
    }

    public function detachContainerFromPresetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->detachContainerFromPresetAction();
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

    public function editLayoutWidgetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editLayoutWidgetAction();
    }

    public function addWidgetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addWidgetAction();
    }

    public function editWidgetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editWidgetAction();
    }

    public function duplicateWidgetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->duplicateWidgetAction();
    }

    public function moveWidgetUpAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveWidgetUpAction();
    }

    public function moveWidgetDownAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveWidgetDownAction();
    }

    public function moveWidgetToContainerAction(): Action
    {
        return $this->layoutBuilderActionFactory()->moveWidgetToContainerAction();
    }

    public function removeWidgetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->removeWidgetAction();
    }

    public function selectAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->selectAssetAction();
    }

    public function addAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->addAssetAction();
    }

    public function editWidgetAssetAction(): Action
    {
        return $this->layoutBuilderActionFactory()->editWidgetAssetAction();
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
